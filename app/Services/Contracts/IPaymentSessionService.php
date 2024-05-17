<?php

namespace App\Services\Contracts;

use App\Models\PaymentSession;
use App\Dtos\PaymentSession as DtoPaymentSession;

interface IPaymentSessionService extends IBaseService
{

    /**
     * Gets the list of supported currencies.
     *
     * @return PaymentSession[]
     */
    public function getPaymentSessions();

    /**
     * Gets the list of supported currencies.
     *
     * @return PaymentSession
     */
    public function getPaymentSession(int $id): PaymentSession;

    /**
     * Adds a currency to the list of supported currencies.
     */
    public function addPaymentSession(DtoPaymentSession $subscriptionOrderDto): PaymentSession;

    /**
     * Adds a currency to the list of supported currencies.
     */
    public function getPaymentSessionByReference(string $ref): PaymentSession;

    /**
     * Updates the specified supported currency.
     */
    public function updatePaymentSession(string $id, DtoPaymentSession $subscriptionOrderDto): PaymentSession;

    /**
     * Removes the specified currency from the list of supported currencies.
     */
    public function deletePaymentSession(string $id);
}
