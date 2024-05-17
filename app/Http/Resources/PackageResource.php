<?php

namespace App\Http\Resources;

use App\Utils\ZeroDecimalCurrencies;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Stripe\Product;
use Stripe\StripeObject;

/**
 * @property Product product
 * @property StripeObject recurring
 * @property StripeObject currency_options
 * @property string id
 */
class PackageResource extends JsonResource
{
    protected array $intervalsPrice = [];

    /**
     * @param  string $interval
     * @param  string $priceId
     * @param  string $currency
     * @param  array  $tiers
     * @return $this
     */
    public function addIntervalPrice(string $interval, string $priceId, string $currency, array $tiers): PackageResource
    {
        array_walk(
            $tiers,
            function ($item) use ($currency) {
                // stripe is not replying with all currencies multiplied by 100.
                $item->unit_amount = (method_exists(ZeroDecimalCurrencies::class, $currency))
                    ? $item->unit_amount * 100
                    : $item->unit_amount;

                // remove few keys which are not needed
                unset($item->flat_amount);
                unset($item->flat_amount_decimal);
                unset($item->unit_amount_decimal);
            }
        );

        $this->intervalsPrice[$interval] = [
            'price_id' => $priceId,
            'tiers' => $tiers
        ];

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $currency = $request->input('currency');
        $currencyOptions = $this->currency_options;
        $this->addIntervalPrice(
            $this->recurring['interval'],
            $this->id,
            $currency,
            $this->currency_options[$currency]['tiers'] ?? []
        );

        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_description' => $this->product->description,
            'product_metadata' => $this->product->metadata,
            'product_images' => $this->product->images,
            'prices_available_in' => array_keys($currencyOptions->toArray()),
            'currency' => $currency,
            'recurring_intervals' => $this->intervalsPrice,
        ];
    }
}
