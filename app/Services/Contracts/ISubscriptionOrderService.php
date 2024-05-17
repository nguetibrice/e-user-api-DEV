<?php

namespace App\Services\Contracts;

use App\Models\SubscriptionOrder;
use App\Dtos\SubscriptionOrder as DtoSubscriptionOrder;

interface ISubscriptionOrderService extends IBaseService
{

    /**
     * Gets the list of supported currencies.
     *
     * @return SubscriptionOrder[]
     */
    public function getSubscriptionOrders();

    /**
     * Gets the list of supported currencies.
     *
     * @return SubscriptionOrder
     */
    public function getSubscriptionOrder(int $id): SubscriptionOrder;

    /**
     * Adds a currency to the list of supported currencies.
     */
    public function addSubscriptionOrder(DtoSubscriptionOrder $subscriptionOrderDto): SubscriptionOrder;

    /**
     * Updates the specified supported currency.
     */
    public function updateSubscriptionOrder(string $id, DtoSubscriptionOrder $subscriptionOrderDto): SubscriptionOrder;

    /**
     * Removes the specified currency from the list of supported currencies.
     */
    public function deleteSubscriptionOrder(string $id);
}
