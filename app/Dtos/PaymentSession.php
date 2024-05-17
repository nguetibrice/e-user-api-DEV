<?php

namespace App\Dtos;

class PaymentSession implements \JsonSerializable
{
    protected ?string $reference;
    protected ?int $order_id;
    protected ?string $price_id;
    protected ?string $order_type;
    protected ?string $payment_method;
    protected ?int $amount;
    protected ?string $currency;
    protected ?string $payment_url;
    protected ?string $payment_token;
    protected ?string $notification_token;
    protected ?int $status;

    public function __construct(
        ?string $reference,
        ?int $order_id,
        ?string $price_id,
        ?string $order_type,
        ?string $payment_method,
        ?int $amount,
        ?string $currency,
        ?string $payment_url = null,
        ?string $payment_token = null,
        ?string $notification_token = null,
        ?int $status = 0
    ) {
        $this->reference = $reference;
        $this->order_id = $order_id;
        $this->price_id = $price_id;
        $this->order_type = $order_type;
        $this->payment_method = $payment_method;
        $this->payment_url = $payment_url;
        $this->payment_token = $payment_token;
        $this->notification_token = $notification_token;
        $this->status = $status;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * Get the id of the language
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set the id of the language
     */
    public function setReference(string $reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get the code of the language
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set the code of the language
     */
    public function setOrderId(int $order_id)
    {
        $this->order_id = $order_id;

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
    public function getOrderType()
    {
        return $this->order_type;
    }

    /**
     * Set the description of the language
     */
    public function setOrderType(string $order_type)
    {
        $this->order_type = $order_type;

        return $this;
    }

    /**
     * Get the status of the language
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the status of the language
     */
    public function setStatus(int $status)
    {
        $this->status = $status;

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
    public function setAmount(int $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get the prices of the language
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * Set the prices of the language
     */
    public function setPaymentMethod(string $payment_method)
    {
        $this->payment_method = $payment_method;

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

    /**
     * Get the prices of the language
     */
    public function getPaymentUrl()
    {
        return $this->payment_url;
    }

    /**
     * Set the prices of the language
     */
    public function setPaymentUrl(string $payment_url)
    {
        $this->payment_url = $payment_url;

        return $this;
    }

    /**
     * Get the prices of the language
     */
    public function getPaymentToken()
    {
        return $this->payment_token;
    }

    /**
     * Set the prices of the language
     */
    public function setPaymentToken(string $payment_token)
    {
        $this->payment_token = $payment_token;

        return $this;
    }

    /**
     * Get the prices of the language
     */
    public function getNotificationToken()
    {
        return $this->notification_token;
    }

    /**
     * Set the prices of the language
     */
    public function setNotificationToken(string $notification_token)
    {
        $this->notification_token = $notification_token;

        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'reference' => $this->reference,
            'order_id' => $this->order_id,
            'price_id' => $this->price_id,
            'order_type' => $this->order_type,
            'payment_method' => $this->payment_method,
            'payment_url' => $this->payment_url,
            'payment_token' => $this->payment_token,
            'notification_token' => $this->notification_token,
            'status' => $this->status,
            'currency' => $this->status,
            'amount' => $this->amount,
        );
    }
}
