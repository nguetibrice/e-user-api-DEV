<?php

namespace App\Jobs;

use App\Models\PaymentSession;
use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Services\Contracts\IOrangeMoneyService;
use App\Services\Contracts\IPriceService;
use App\Services\Contracts\ISubscriptionService;
use App\Services\Contracts\IUserService;
use App\Wallet\Stripe\Requests\TransactionRequest;
use App\Wallet\Stripe\Wallet;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Pool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool as ClientPool;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Log;
use Throwable;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class OrangeMoneyCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return bool
     */

    /**
     * Execute the job.
     *
     * @return
     */
    public function handle()
    {
        try {
            $sessions = PaymentSession::where('status', 0)
            ->where("created_at", Carbon::now()->subDay())->limit(20)->get();
            $uuids = [];
            foreach ($sessions as $session) {
                $uuids[] = $session->reference;
            }

            // function to build e-user-api callback endpoint based on uuid
            $getUrl = fn (string $uuid) => url("/api/v1/payment/callback?uuid=$uuid");

             // requests to be sent as generator
            $requests = function (array $uuids) use ($getUrl) {
                foreach ($uuids as $uuid) {
                    yield new Request('GET', $getUrl($uuid));
                }
            };

             // timeout number can be increase for something more realistic
            $client = new Client(['timeout' => 2]);
            $pool = new Pool(
                $client,
                $requests($uuids),
                [
                    'concurrency' => 20, // you can store this number in the .env file
                    'fulfilled' => function (Response $response, $index) use ($uuids, $getUrl) {
                        echo '<pre>';
                        var_dump([
                            'id' => $index,
                            'request' => $getUrl($uuids[$index]),
                            'response' => $response->getBody()->getContents()
                        ]);
                        echo '</pre>';
                    },
                    'rejected' => function (\Exception $exception, $index) use ($uuids, $getUrl) {
                        // you can log error to opensearch
                        echo '<pre>';
                        var_dump([
                            'id' => $index,
                            'request' => $getUrl($uuids[$index]),
                            'exception' => [
                                'line' => $exception->getLine(),
                                'file' => $exception->getFile(),
                                'message' => $exception->getMessage(),
                            ]
                        ]);
                        echo '</pre>';
                    }
                ]
            );
            $promise = $pool->promise();

            // wait for all responses
            $promise->wait();
            // return $results;
            return true;
        } catch (\Throwable $th) {
            //throw $th;
            Log::error(
                "OM_CHECK_STATUS: Something went wrong",
                ["error" => $th->getMessage(), "body" => json_encode($th)]
            );
            return $this->failed(new Exception($th->getMessage(), 400, $th));
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        Log::error(
            "OM_CHECK_STATUS: Something went wrong",
            ["error" => $exception->getMessage(), "body" => json_encode($exception->getTrace())]
        );
        // Send user notification of failure, etc...
    }
}
