<?php

namespace App\Services\Contracts;

use App\Models\RechargeOrder;
use App\Dtos\RechargeOrder as DtoRechargeOrder;

interface IRechargeOrderService extends IBaseService
{

    /**
     * Gets the list of supported currencies.
     *
     * @return RechargeOrder[]
     */
    public function getRechargeOrders();

    /**
     * Gets the list of supported currencies.
     *
     * @return RechargeOrder
     */
    public function getRechargeOrder(int $id): RechargeOrder;

    /**
     * Adds a currency to the list of supported currencies.
     */
    public function addRechargeOrder(DtoRechargeOrder $subscriptionOrderDto): RechargeOrder;

    /**
     * Updates the specified supported currency.
     */
    public function updateRechargeOrder(string $id, DtoRechargeOrder $subscriptionOrderDto): RechargeOrder;

    /**
     * Removes the specified currency from the list of supported currencies.
     */
    public function deleteRechargeOrder(string $id);
}
