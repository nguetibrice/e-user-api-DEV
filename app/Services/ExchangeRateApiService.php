<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Services\Contracts\ExchangeRateApiContract;
use App\Exceptions\NotImplementedException;
use Illuminate\Support\Facades\Http;

class ExchangeRateApiService implements ExchangeRateApiContract
{
    /**
     * @inheritDoc
     * @throws NotImplementedException
     */
    public function fetch(string $currency): array
    {
        // todo return static values for all currencies. keep in mind that this is a temporary solution
        $res = Http::acceptJson()
        ->withHeaders([
            "X-CoinAPI-Key" => env("CURRENCY_API_KEY")
        ])
        ->get(env("CURRENCY_API_BASE_URL"). "/exchangerate/". strtoupper(env("CASHIER_CURRENCY")));

        if ($res->status() != 200 && $res->status() != 201) {
            return ["error" => $res->body()];
        }

        return json_decode($res->body(), true);
    }
}
