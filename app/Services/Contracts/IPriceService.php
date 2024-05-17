<?php

namespace App\Services\Contracts;

use Stripe\Price;

interface IPriceService
{
    /**
     * Gets a price from the stripe database.
     */
    public function getPrice(string $id): Price;

    /**
     * Gets the list of prices saved in the stripe database.
     *
     * @param array $filters A list of filters to filter the prices.
     */
    public function getPrices(array $filters = []): array;

    /**
     * Adds a new price for the specified language.
     */
    public function addPrice(array $attributes): Price;

    /**
     * Updates a specified price of a language.
     */
    public function updatePrice(string $id, array $updates);

    /**
     * Deletes the specified price of a language.
     */
    public function deletePrice(string $id): void;
}
