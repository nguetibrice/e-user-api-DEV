<?php

namespace App\Wallet\OrangeMoney\Requests;

use App\Wallet\BaseWalletRequest;

class TransactionRequest extends BaseWalletRequest
{
    protected $type;
    protected int $amount;
    protected string $currency;
    protected string $ref;
    public function __construct(
        string $type,
        string $customerId = null,
        int $amount = null,
        int $ref = null,
        string $currency = null
    ) {
        $this->type = $type;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->ref = $ref;
        parent::__construct($customerId);
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }
    public function getCurrency(): string
    {
        return $this->currency;
    }
    public function getRef(): string
    {
        return $this->ref;
    }
}
