<?php

namespace App\Dtos;

class Subscription implements \JsonSerializable
{
    protected ?string $stripe_id;
    protected ?string $name;
    protected ?string $stripe_status;
    protected ?string $stripe_price;
    protected int $quantity;

    public function __construct(
        ?string $stripe_id = null,
        ?string $name = null,
        ?string $stripe_price = null,
        int $quantity = 1,
        ?string $stripe_status = null
    ) {
        $this->stripe_id = $stripe_id;
        $this->name = $name;
        $this->stripe_price = $stripe_price;
        $this->quantity = $quantity;
        $this->stripe_status = $stripe_status;
    }

    /**
     * Get the id of the language
     */
    public function getStripeId()
    {
        return $this->stripe_id;
    }

    /**
     * Set the id of the language
     */
    public function setId(string $stripe_id)
    {
        $this->stripe_id = $stripe_id;

        return $this;
    }


    /**
     * Get the name of the language
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the language
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }


    /**
     * Get the status of the language
     */
    public function getStripeStatus()
    {
        return $this->stripe_status;
    }

    /**
     * Set the status of the language
     */
    public function setStripeStatus(string $stripe_status)
    {
        $this->stripe_status = $stripe_status;

        return $this;
    }


    /**
     * Get the prices of the language
     */
    public function getStripePrice()
    {
        return $this->stripe_price;
    }

    /**
     * Set the prices of the language
     */
    public function setStripePrice(string $stripe_price)
    {
        $this->stripe_price = $stripe_price;

        return $this;
    }


    /**
     * Get the code of the language
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set the code of the language
     */
    public function setCode(int $quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'stripe_id' => $this->stripe_id,
            'quantity' => $this->quantity,
            'name' => $this->name,
            'stripe_status' => $this->stripe_status,
            'stripe_price' => $this->stripe_price,
            'trial_ends_at' => null,
            'ends_at' => null,
        );
    }
}
