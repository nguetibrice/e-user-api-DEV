<?php

namespace App\Controllers;

use App\Services\Contracts\ExchangeRateContract;
use Illuminate\Http\Request;

class CurrencyExchangeRateController
{
    protected ExchangeRateContract $exchangeRateContract;
    public function getExchangeRates(Request $request, ?string $date = null)
    {
        // todo
    }
}
