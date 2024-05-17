<?php

namespace App\Wallet;

class BaseWalletRequest
{
    protected string $customerId;

    public function __construct(string $customerId)
    {
        $this->customerId = $customerId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }
}
