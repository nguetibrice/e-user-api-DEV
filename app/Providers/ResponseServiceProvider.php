<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro(
            'success',
            function ($data, int $status = HttpResponse::HTTP_OK): JsonResponse {
                $success = 'success';
                $response = (empty($data)) ? ['status' => $success] : ['status' => $success, 'data' => $data];

                return response()->json($response, $status, ['Content-Type' => 'application/json']);
            }
        );

        Response::macro(
            'error',
            function (string $reason, int $status = HttpResponse::HTTP_BAD_REQUEST, array $extra = []): JsonResponse {
                $data = ['status' => 'error', 'reason' => $reason];
                $response = $data + $extra;

                return response()->json(
                    $response,
                    $status,
                    ['Content-Type' => 'application/json'],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                );
            }
        );
    }
}
