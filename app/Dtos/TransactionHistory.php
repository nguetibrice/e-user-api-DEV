<?php

namespace App\Dtos;

class TransactionHistory implements \JsonSerializable
{
    protected ?int $user_id;
    protected ?string $type;
    protected ?float $amount;
    protected ?string $motif = null;
    protected ?string $currency;

    public function __construct(
        ?int $user_id,
        ?string $type,
        ?float $amount,
        ?string $currency,
        ?string $motif = null
    ) {
        $this->user_id = $user_id;
        $this->type = $type;
        $this->amount = $amount;
        $this->motif = $motif;
        $this->currency = $currency;
    }

    /**
     * Get the id of the language
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set the id of the language
     */
    public function setUserId(int $user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get the code of the language
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the code of the language
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the status of the language
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set the status of the language
     */
    public function setAmount(float $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get the name of the language
     */
    public function getMotif()
    {
        return $this->motif;
    }

    /**
     * Set the name of the language
     */
    public function setMotif(string $motif)
    {
        $this->motif = $motif;

        return $this;
    }


    /**
     * Get the prices of the language
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set the prices of the language
     */
    public function setCurrency(string $currency)
    {
        $this->currency = $currency;

        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'user_id' => $this->user_id,
            'amount' => $this->amount,
            'type' => $this->type,
            'motif' => $this->motif,
            'currency' => $this->currency
        );
    }
}
