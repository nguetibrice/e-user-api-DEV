<?php

namespace App\Services;

use Stripe\Price;
use Stripe\Stripe;
use Illuminate\Support\Facades\Cache;
use App\Services\Contracts\IPriceService;
use App\Services\Contracts\ICurrencyService;

class PriceService implements IPriceService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function getPrice(string $id): Price
    {
        $currencies = app()->make(ICurrencyService::class)->getCurrencies();
        $results = array_filter($this->retrievePrices($currencies), function ($price) use ($id) {
            return $price->id === $id;
        });

        if (empty($results)) {
            throw new \RuntimeException("The price referred to by '$id' does not exist.");
        }

        $price = array_shift($results);

        return $price;
    }

    public function getPrices(array $filters = []): array
    {
        $currencies = app()->make(ICurrencyService::class)->getCurrencies();
        $prices = $this->retrievePrices($currencies);

        if ($filters) {
            // Validate the filters
            $this->validateFilters($filters);

            // Apply the filters
            $prices = array_values(array_filter($prices, function ($price) use ($filters) {
                $conditions = [];
                foreach ($filters as $key => $value) {
                    $conditions[] = '$price->{$key} === $value';
                }
                $selection_condition = implode(" and ", $conditions);
                $selected = eval("return " . $selection_condition . ';');

                return $selected;
            }));
        }

        return $prices;
    }

    public function addPrice(array $attributes): Price
    {
        $price = Price::create($attributes);

        $this->refreshCache();

        return $price;
    }

    public function updatePrice(string $id, array $attributes)
    {
        $price = Price::update($id, $attributes);

        $this->refreshCache();

        return $price;
    }

    public function deletePrice(string $id): void
    {
        $this->updatePrice($id, ['active' => false]);
    }

    protected function retrievePrices(array $currencies)
    {
        $pricing_tiers = [];
        foreach ($currencies as $currency) {
            $pricing_tiers[] = "data.currency_options." . strtolower($currency->code) . ".tiers";
        }

        return Cache::remember('prices', now()->addHour(), function () use ($pricing_tiers) {
            return Price::search([
                "query" => "active:'true'",
                "expand" => $pricing_tiers
            ])->data;
        });
    }

    protected function refreshCache()
    {
        Cache::forget('prices');
    }

    protected function validateFilters(array &$filters)
    {
        array_walk($filters, function ($value, $key) {
            if (!in_array(strtolower($key), ['language', 'currency'])) {
                throw new \RuntimeException("Filter '" . strtolower($key) . "' is not valid");
            }
        });
        if (array_key_exists('language', $filters)) {
            // Change the key 'language' to 'product'
            $keys = array_keys($filters);
            $keys[array_search('language', $keys)] = 'product';
            $filters = array_combine($keys, $filters);
        }
    }
}
