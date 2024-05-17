<?php

namespace App\Services\Contracts;

use App\Models\TransactionHistory;
use App\Dtos\TransactionHistory as DtoTransactionHistory;

interface ITransactionHistoryService extends IBaseService
{

    /**
     * Gets the list of supported currencies.
     *
     * @return TransactionHistory[]
     */
    public function getTransactionHistories();

    /**
     * Gets the list of supported currencies.
     *
     * @return TransactionHistory
     */
    public function getTransactionHistory(int $id): TransactionHistory;

    /**
     * Adds a currency to the list of supported currencies.
     */
    public function addTransactionHistory(DtoTransactionHistory $subscriptionOrderDto): TransactionHistory;

    /**
     * Updates the specified supported currency.
     */
    public function updateTransactionHistory(string $id, DtoTransactionHistory $subscriptionOrderDto): TransactionHistory;

    /**
     * Removes the specified currency from the list of supported currencies.
     */
    public function deleteTransactionHistory(string $id);
}
