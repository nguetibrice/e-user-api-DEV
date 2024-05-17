<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use App\Services\Contracts\ICurrencyService;

class CurrencyService extends BaseService implements ICurrencyService
{
    public function getCurrencies()
    {
        return Cache::remember('currencies', now()->addHour(), function () {
            $currencies = [];

            foreach (Currency::all() as $currency) {
                $currencies[] = $currency;
            };

            return $currencies;
        });
    }

    public function getCurrenciesByKey(string $key, $value)
    {
        return Currency::where($key,$value)->get();
    }



    public function addCurrency(string $name, string $code): Currency
    {
        $currency = new Currency(['name' => $name, 'code' => $code]);

        $this->insert($currency); // Store the currency

        Cache::forget('currencies');

        return $currency;
    }

    public function updateCurrency(string $id, array $data): Currency
    {
        $currency = $this->findOneById($id);

        if ($data) {
            foreach ($data as $key => $value) {
                $currency->{$key} = $value;
            }

            $this->update($currency); // Update the currency

            Cache::forget('currencies');
        }

        return $currency;
    }

    public function deleteCurrency(string $id)
    {
        $currency = $this->findOneById($id);

        $this->delete($currency); // Remove the currency

        Cache::forget('currencies');
    }

    protected function getModelObject(): Currency
    {
        return new Currency();
    }
}
