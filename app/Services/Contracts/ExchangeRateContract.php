<?php

namespace App\Services\Contracts;

use App\Models\ExchangeRate;

interface ExchangeRateContract
{
    /**
     * Save list of given exchange rates in database
     *
     * @param ExchangeRate[] $exchangeRates
     * @return void
     */
    public function saveMany(array $exchangeRates): void;

    /**
     * Gets list of exchange rates for a given date
     *
     * @param string $date
     * @return ExchangeRate[]
     */
    public function fetchByDate(string $date): array;
}
