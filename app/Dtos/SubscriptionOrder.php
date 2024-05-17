<?php

namespace App\Dtos;

class SubscriptionOrder implements \JsonSerializable
{
    protected ?string $user_id;
    protected ?string $product_name;
    protected int $quantity;
    protected ?string $price_id;
    protected ?string $description;
    protected ?string $currency;

    public function __construct(
        ?string $user_id = null,
        ?string $product_name = null,
        ?int $quantity = 1,
        ?string $price_id = null,
        ?string $currency = null,
        ?string $description = null
    ) {
        $this->user_id = $user_id;
        $this->product_name = $product_name;
        $this->quantity = $quantity;
        $this->description = $description;
        $this->price_id = $price_id;
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
    public function setUserId(string $user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get the code of the language
     */
    public function getProductName()
    {
        return $this->product_name;
    }

    /**
     * Set the code of the language
     */
    public function setProductName(string $product_name)
    {
        $this->product_name = $product_name;

        return $this;
    }

    /**
     * Get the name of the language
     */
    public function getPriceId()
    {
        return $this->price_id;
    }

    /**
     * Set the name of the language
     */
    public function setPriceId(string $price_id)
    {
        $this->price_id = $price_id;

        return $this;
    }

    /**
     * Get the description of the language
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the description of the language
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the status of the language
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set the status of the language
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;

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
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'description' => $this->description,
            'price_id' => $this->price_id,
            'currency' => $this->currency
        );
    }
}
