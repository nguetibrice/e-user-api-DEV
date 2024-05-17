<?php

namespace App\Services\Contracts;

use App\Models\Currency;

interface CurrencyContract
{
    /**
     * Gets list of all currencies
     *
     * @return Currency[]
     */
    public function fetchAll(array $status = [0, 1]): array;

    /**
     * Insert new currency in database
     *
     * @param Currency $currency
     * @return Currency
     */
    public function insert(Currency $currency): Currency;

    /**
     * Updates the given currency
     *
     * @param Currency $currency
     * @return Currency
     */
    public function update(Currency $currency): Currency;

    /**
     * Disables the given currency
     *
     * @param Currency $currency
     * @return Currency
     */
    public function delete(Currency $currency): Currency;
}
