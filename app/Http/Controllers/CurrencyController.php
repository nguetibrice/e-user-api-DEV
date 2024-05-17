<?php

namespace App\Http\Controllers;

use App\Rules\CurrencyCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use App\Services\Contracts\ICurrencyService;

class CurrencyController extends Controller
{
    protected ICurrencyService $currencyService;

    public function __construct(ICurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Display a listing of currencies supported.
     */
    public function index(): JsonResponse
    {
        $currencies = $this->currencyService->getCurrencies();
        return Response::success(['currencies' => $currencies]);
    }

    /**
     * Display the specified currency.
     */
    public function show(string $id): JsonResponse
    {
        $currency = $this->currencyService->findOneById($id);
        return Response::success(['currency' => $currency]);
    }

    /**
     * Store a newly created currency in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'code' => ['required', 'string', new CurrencyCode()],
        ]);

        $currency = $this->currencyService->addCurrency($data['name'], $data['code']);

        return Response::success(['currency' => $currency]);
    }

    /**
     * Update the specified currency in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'code' => ['sometimes', 'string', new CurrencyCode()],
        ]);

        $currency = $this->currencyService->updateCurrency($id, $data);

        return Response::success(['currency' => $currency]);
    }

    /**
     * Remove the specified currency from storage.
     */
    public function delete(string $id): JsonResponse
    {
        $this->currencyService->deleteCurrency($id);
        return Response::success(['message' => "The currency #$id was successfully deleted"]);
    }
}
