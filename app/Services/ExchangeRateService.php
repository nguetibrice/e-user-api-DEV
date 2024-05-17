<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Services\Contracts\ExchangeRateContract;
use App\Exceptions\NotImplementedException;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService implements ExchangeRateContract
{
    /**
     * @inheritDoc
     */
    public function saveMany(array $exchangeRates)
    {
        foreach ($exchangeRates as $rate) {
            $rate->save();
        }
    }

    /**
     * @inheritDoc
     */
    public function fetchByDate(string $date)
    {
        return ExchangeRate::whereBetween("created_at", [$date." 00:00" , $date." 23:59"])->get();
    }
}
