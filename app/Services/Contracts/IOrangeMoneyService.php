<?php

namespace App\Services\Contracts;

use App\Models\PaymentSession;

interface IOrangeMoneyService
{
    /**
     * Make Payment request to Orange Money.
     */
    public function pay(array $data): array;

    /**
     * Gets the list of languages saved in the stripe database.
     *
     * @return Language[]
     */
    // public function getLanguages($page = null): array;

    /**
     * Check status of payment.
     */
    public function checkStatus(PaymentSession $session, $order): array;

    /**
     * Updates a language in the stripe database.
     */
    // public function updateLanguage(string $id, array $updates): Language;

    /**
     * Removes a language from the stripe database.
     */
    // public function deleteLanguage(string $id): void;
}
