<?php

namespace App\Dtos;

class Language implements \JsonSerializable
{
    protected ?string $id;
    protected ?string $code;
    protected ?string $name;
    protected ?string $description;
    protected int $status;
    protected array $prices;

    public function __construct(
        ?string $id = null,
        ?string $code = null,
        ?string $name = null,
        ?string $description = null,
        int $status = 0,
        array $prices = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->status = $status;
        $this->prices = $prices;
    }

    /**
     * Get the id of the language
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the id of the language
     */
    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the code of the language
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set the code of the language
     */
    public function setCode(string $code)
    {
        $this->code = $code;

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
     * Get the prices of the language
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * Set the prices of the language
     */
    public function setPrices(array $prices)
    {
        $this->prices = $prices;

        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'prices' => $this->prices
        );
    }
}
