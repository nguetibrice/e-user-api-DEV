<?php

namespace App\Services\Contracts;

use App\Models\PaymentMethod;

interface IPaymentMethodService extends IBaseService
{
    /**
     * Gets the list of supported payment methods.
     *
     * @return PaymentMethod[]
     */
    public function getPaymentMethods();

    /**
     * Adds a payment method to the list of supported payment methods.
     */
    public function addPaymentMethod(array $data): PaymentMethod;

    /**
     * Updates the specified supported payment method.
     */
    public function updatePaymentMethod(string $id, array $data): PaymentMethod;

    /**
     * Removes the specified payment method from the list of supported payment methods.
     */
    public function deletePaymentMethod(string $id);

    /**
     * Get the specified payment method.
     */
    public function findPaymentMethod(string $id);
}
