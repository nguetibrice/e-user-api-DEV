<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use App\Services\Contracts\IPriceService;

class PriceController extends Controller
{
    protected IPriceService $priceService;

    public function __construct(IPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    /**
     * Display a listing of prices.
     */
    public function index(Request $request): JsonResponse
    {
        $prices = $this->priceService->getPrices();

        return Response::success(['prices' => $prices]);
    }

    /**
     * Display the specified price.
     *
     * @param  int  $id The language's identifier
     */
    public function show(string $id): JsonResponse
    {
        $price = $this->priceService->getPrice($id);

        return Response::success(['price' => $price]);
    }

    /**
     * Add a new price for a given language.
     */
    public function store(Request $request): JsonResponse
    {
        $attributes = $request->only([
            'currency',
            'product',
            'recurring',
            'billing_scheme',
            'active',
            'tiers_mode',
            'tiers',
        ]);

        $price = $this->priceService->addPrice($attributes);

        return Response::success(['price' => $price]);
    }

    /**
     * Update the specified price of a language.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            "tiers" => "required|array",
            "currency"=> "required|string"
        ]);

        $currency = strtolower($request->currency);
        $data = [
            "currency_options" => [
                $currency => ["tiers" => $request->tiers]
            ]
        ];
        $price = $this->priceService->updatePrice($id, $data);

        return Response::success(['price' => $price]);
    }

    public function delete(string $id): JsonResponse
    {
        $this->priceService->deletePrice($id);
        return Response::success(['message' => "Price '$id' was deleted successfully"]);
    }
}
