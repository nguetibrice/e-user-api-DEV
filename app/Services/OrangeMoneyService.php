<?php

namespace App\Services;

use App\Models\SubscriptionOrder;
use App\Services\Contracts\IPriceService;
use Stripe\Stripe;
use Stripe\Product;
use App\Dtos\Language;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use App\Services\Contracts\IOrangeMoneyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Request;

class OrangeMoneyService implements IOrangeMoneyService
{
    protected IPriceService $priceService;
    public function __construct(IPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    protected function getToken() : array
    {
        $response = Http::asForm()->withHeaders(["Authorization" => "Basic ".env('ORANGE_MONEY_CONSUMER_KEY')])
        ->post(env("ORANGE_MONEY_BASE_URL")."/oauth/v3/token", ["grant_type" => "client_credentials"]);
        return json_decode($response->body(), true);
    }

    public function pay(array $data) : array
    {
        // make try
        try {
            if (env("ORANGE_MONEY_BASE_URL") == null
                || env("ORANGE_MONEY_MERCHANT_KEY") == null
                || env("ORANGE_MONEY_CONSUMER_KEY") == null
            ) {
                Log::error(
                    "ORANGEMONEY_PAYMENT: MISSING MANDATORY PARAMETERS",
                    [
                        "base_url" => env("ORANGE_MONEY_BASE_URL"),
                        "merchant_key" => env("ORANGE_MONEY_MERCHANT_KEY"),
                        "consumer_key" => env("ORANGE_MONEY_CONSUMER_KEY"),
                    ]
                );
                return ["error" => "ORANGEMONEY_PAYMENT: MISSING MANDATORY PARAMETERS"];
            }
            $token_data = $this->getToken();
            if (!isset($token_data["access_token"])) {
                Log::error("ORANGEMONEY_PAYMENT: Unable to get Access Token", ["error" => $token_data]);
                return ["error" => $token_data];
            }
            $data["merchant_key"] = env("ORANGE_MONEY_MERCHANT_KEY");
            $response = Http::acceptJson()
            ->withToken($token_data["access_token"])
            ->post(env("ORANGE_MONEY_BASE_URL")."/orange-money-webpay/cm/v1/webpayment", $data);
            $response_data = json_decode($response->body(), true);

            if ($response->status() != 200 && $response->status() != 201) {
                Log::error(
                    "ORANGEMONEY_PAYMENT: Something went wrong",
                    ["error" => $response_data, "body" => $data]
                );
                return ["error" => $response_data];
            }
            Log::info(
                "ORANGEMONEY_PAYMENT: Transaction Successful",
                ["response_data" => $response_data, "body" => $data]
            );
            return ["data" => $response_data];
        } catch (\Throwable $th) {
            $error = ["error" => [
                "file" => $th->getFile(),
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
            ]];
            $wallet = [
                'wallet' => 'orange',
                'base_url' => env("ORANGE_MONEY_BASE_URL")."/orange-money-webpay/cm/v1/webpayment",
                'payload' => json_encode($data)
            ];
            Log::error(
                "ORANGE_MONEY_PAYMENT: Something went wrong",
                ["exception" => $th, "third_party" => $wallet]
            );
            return $error;
        }
    }

    public function checkStatus($session, $order) : array
    {
        // make try
        try {
            if (env("ORANGE_MONEY_BASE_URL") == null
                || env("ORANGE_MONEY_MERCHANT_KEY") == null
                || env("ORANGE_MONEY_CONSUMER_KEY") == null
            ) {
                Log::error(
                    "ORANGEMONEY_PAYMENT: MISSING MANDATORY PARAMETERS",
                    [
                        "base_url" => env("ORANGE_MONEY_BASE_URL"),
                        "merchant_key" => env("ORANGE_MONEY_MERCHANT_KEY"),
                        "consumer_key" => env("ORANGE_MONEY_CONSUMER_KEY"),
                    ]
                );
                return ["error" => "ORANGEMONEY_PAYMENT: MISSING MANDATORY PARAMETERS"];
            }
            $token_data = $this->getToken();
            if (!isset($token_data["access_token"])) {
                Log::error("CHECK_TRANSACTION_STATUS_OM: Unable to get Access Token", ["error" => $token_data]);
                return ["error" => $token_data];
            }
            Log::info(
                "ORANGE_MONEY_CHECK_STATUS: Parameters: ". json_encode(["order" => $order, "session" => $session])
            );
            $price = $this->priceService->getPrice($order->price_id);
            $amount = 0;
            if ($order->quantity >= 10) {
                $tiers = $price["currency_options"][strtolower($order->currency)]["tiers"];
                foreach ($tiers as $tier) {
                    if ($tier["up_to"] == null) {
                        $amount = $tier["flat_amount"];
                    }
                }
            } else {
                $nearest = null;
                $tiers = $price["currency_options"][strtolower($order->currency)]["tiers"];
                foreach ($tiers as $tier) {
                    if ($tier["up_to"] != null) {
                        if ($tier["up_to"] >= $order->quantity) {
                            $amount = $tier["flat_amount"];
                            if ($tier["up_to"] - $order->quantity < $nearest - $order->quantity
                                || $nearest === null
                            ) {
                                $amount = $tier["flat_amount"];
                                $nearest = $tier["up_to"];
                            }
                        }
                    }
                }
            }
            $amount *=  $order->quantity;
            $data = [
                "order_id" => $session->reference,
                "amount" => $amount,
                "pay_token" => $session->payment_token
            ];
            $response = Http::acceptJson()
            ->withToken($token_data["access_token"])
            ->post(env("ORANGE_MONEY_BASE_URL")."/orange-money-webpay/cm/v1/transactionstatus", $data);
            $response_data = json_decode($response, true);
            if ($response->status() != 200 && $response->status() != 201) {
                Log::error(
                    "CHECK_TRANSACTION_STATUS_OM: Something went wrong: ".
                    json_encode(["error" => $response_data, "body" => $data]),
                    ["error" => $response_data, "body" => $data]
                );
                return ["error" => $response_data];
            }
            Log::info(
                "CHECK_TRANSACTION_STATUS_OM: Check Status Successful",
                ["response_data" => $response_data, "body" => $data]
            );
            return ["data" => $response_data];
        } catch (\Throwable $th) {
            $error = ["error" => [
                "file" => $th->getFile(),
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
            ]];
            Log::error(
                "ORANGE_MONEY_CHECK_STATUS: Something went wrong: ". json_encode($error),
                ["exception" => $th]
            );
            return $error;
        }
    }
}
