<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class PackageCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $collection = $this->collection->groupBy(
            function (PackageResource $item) use ($request) {
                return $item->product->id;
            }
        )->map(
            function (Collection $prices) use ($request) {
                /**
                 * @var PackageResource $product
                 */
                $product = $prices->shift();
                $currency = $request->input('currency');

                foreach ($prices as $price) {
                    $product->addIntervalPrice(
                        $price->recurring['interval'],
                        $price->id,
                        $currency,
                        $price->currency_options[$currency]['tiers'] ?? []
                    );
                }

                return $product;
            }
        )->values()->all();

        return ['packages' => $collection];
    }
}
