<?php

namespace App\Wallet;

class BaseWalletResponse
{
    protected ?string $customerId = null;
    protected $error = null;
    protected ?string $currency = null;
    protected ?float $balance = null;

    public function __construct(
        string $customerId = null,
        string $currency = null,
        $balance = null
    ) {
        $this->customerId = $customerId;
        $this->currency = $currency;
        $this->balance = $balance;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBalance()
    {
        return $this->balance;
    }

    public function setError(\Throwable $th) : void
    {
        $this->error = [
            "message"=> $th->getMessage(),
            "file" => $th->getFile(),
            "line"=> $th->getLine(),
            "code"=> $th->getCode(),
            "stack_trace"=> $th->getTraceAsString(),
        ];
    }
}
