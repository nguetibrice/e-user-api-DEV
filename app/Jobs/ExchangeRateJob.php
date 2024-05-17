<?php

namespace App\Jobs;

use App\Models\ExchangeRate;
use App\Services\Contracts\ExchangeRateApiContract;
use App\Services\Contracts\ICurrencyService;
use App\Services\Contracts\ExchangeRateContract;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExchangeRateJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected ExchangeRateApiContract $apiExchangeRatesService;
    protected ExchangeRateContract $exchangeRateService;
    protected ICurrencyService $currencyService;

    /**
     * ExchangeRateJob constructor.
     *
     * @param ExchangeRateApiContract $apiExchangeRatesService
     * @param ExchangeRateContract $exchangeRateService
     * @param ICurrencyService $currencyService
     */
    public function __construct(
        ExchangeRateApiContract $apiExchangeRatesService,
        ExchangeRateContract $exchangeRateService,
        ICurrencyService $currencyService
    ) {
        $this->apiExchangeRatesService = $apiExchangeRatesService;
        $this->exchangeRateService = $exchangeRateService;
        $this->currencyService = $currencyService;
    }

    /**
     * Update exchange rates twice a day
     */
    public function handle(): void
    {
        try {
            // todo get base currency from .env file
            $currency = env("CASHIER_CURRENCY");
            // todo get exchange rates from the external api
            $rates = $this->apiExchangeRatesService->fetch($currency);
            // todo get list of active currencies (currencies with status = 1)
            $currencies = $this->currencyService->getCurrenciesByKey("status", 1);

            foreach ($currencies as $currency) {
                foreach ($rates as $rate) {
                    if(strtoupper($currency->code) == strtoupper($rate["currency"])) {
                        // todo save exchange rates for active currencies only
                        $exchange_rate = new ExchangeRate([
                            "currency_id" => $currency->id,
                            "rate" => $rate["rate"]
                        ]);
                        $data[] = $exchange_rate;
                    }
                }
            }
            $this->exchangeRateService->saveMany($data);
        } catch (\Throwable $th) {
            Log::error("EXCHANGE RATE ERROR:". json_encode([
                "error" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
            ]));
            throw $th;
        }

    }
}
