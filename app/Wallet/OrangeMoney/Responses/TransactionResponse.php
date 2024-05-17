<?php

namespace App\Wallet\OrangeMoney\Responses;

use App\Wallet\BaseWalletResponse;

class TransactionResponse extends BaseWalletResponse
{
    protected string $payment_url;
    protected string $notif_token;
    public function __construct(
        string $payment_url = null,
        string $notif_token = null,
        string $customerId = null,
        string $currency = null,
        float $balance = null
    ) {
        $this->payment_url = $payment_url;
        $this->notif_token = $notif_token;
        $this->currency = $currency;
        parent::__construct($customerId, $currency, $balance);
    }

    public function getPaymentUrl(): string
    {
        return $this->payment_url;
    }
    public function getNotifToken(): string
    {
        return $this->notif_token;
    }
}
