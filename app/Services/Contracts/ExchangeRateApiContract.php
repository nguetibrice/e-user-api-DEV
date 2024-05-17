<?php

namespace App\Services\Contracts;

use App\Models\ExchangeRate;

interface ExchangeRateApiContract
{
    /**
     * Calls an external API to get list of exchange rates for a given currency
     *
     * @param string $currency
     * @return ExchangeRate[]
     */
    public function fetch(string $currency): array;
}
