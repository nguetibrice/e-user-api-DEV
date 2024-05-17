<?php

namespace App\Wallet\Stripe\Requests;

use App\Wallet\BaseWalletRequest;

class TransactionRequest extends BaseWalletRequest
{
    protected $type;
    protected int $amount;
    protected string $currency;
    protected ?string $motif = null;
    public function __construct(
        string $type,
        string $customerId = null,
        int $amount = null,
        string $currency = null,
        string $motif = null

    ) {
        $this->type = $type;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->motif = $motif;
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
    public function getMotif(): string|null
    {
        return $this->motif;
    }

}
