<?php

namespace App\Services\Contracts;

use App\Models\Currency;

interface ICurrencyService extends IBaseService
{
    /**
     * Gets the list of supported currencies.
     *
     * @return Currency[]
     */
    public function getCurrencies();

    /**
     * Adds a currency to the list of supported currencies.
     */
    public function addCurrency(string $name, string $code): Currency;

    /**
     * Updates the specified supported currency.
     */
    public function updateCurrency(string $id, array $data): Currency;

    /**
     * Removes the specified currency from the list of supported currencies.
     */
    public function deleteCurrency(string $id);

    /**
     * Removes the specified currency from the list of supported currencies.
     */
    public function getCurrenciesByKey(string $key, $value);


}
