<?php

namespace App\Utils;

final class ZeroDecimalCurrencies
{
    protected string $base;

    protected function __construct(string $base)
    {
        $this->base = $base;
    }

    public static function BIF(): self
    {
        return new self('bif');
    }

    public static function CLP(): self
    {
        return new self('clp');
    }

    public static function DJF(): self
    {
        return new self('djf');
    }

    public static function GNF(): self
    {
        return new self('gnf');
    }

    public static function JPY(): self
    {
        return new self('jpy');
    }

    public static function KMF(): self
    {
        return new self('kmf');
    }

    public static function KRW(): self
    {
        return new self('krw');
    }

    public static function MGA(): self
    {
        return new self('mga');
    }

    public static function PYG(): self
    {
        return new self('pyg');
    }

    public static function RWF(): self
    {
        return new self('rwf');
    }

    public static function UGX(): self
    {
        return new self('ugx');
    }

    public static function VND(): self
    {
        return new self('vnd');
    }

    public static function VUV(): self
    {
        return new self('vuv');
    }

    public static function XAF(): self
    {
        return new self('xaf');
    }

    public static function XOF(): self
    {
        return new self('xof');
    }

    public static function XPF(): self
    {
        return new self('xpf');
    }

    public function __toString(): string
    {
        return $this->base;
    }
}
