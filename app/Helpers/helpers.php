<?php

use App\Models\User;
use App\Services\Contracts\ICurrencyService;
use App\Services\Contracts\ITransactionHistoryService;
use App\Services\Contracts\IUserService;
use App\Wallet\Stripe\Requests\TransactionRequest;
use App\Wallet\Stripe\Wallet;
use Stripe\Customer;
use Stripe\PaymentMethod;
// use AmrShawky\LaravelCurrency\Facade\Currency;

function processRecharge(
    User $user,
    $amount,
    $currency,
    $type,
    ITransactionHistoryService $transactionHistoryService,
    IUserService $userService,
    PaymentMethod $paymentMethod = null,
    User $target = null
) {

    if ($type == "CREDIT" && $paymentMethod != null) {
        $user->charge($amount,$paymentMethod);
    }
    if ($type == "TRANSFER") {
        $customer = verifyCustomer($target);
    }else {
        $customer = verifyCustomer($user);
    }

    $transaction_request = new TransactionRequest(
        $type,
        $customer["id"],
        $amount,
        strtolower($currency)
    );

    $wallet = new Wallet(
        $transactionHistoryService,
        $userService
    );
    if ($type == "CREDIT" || $type == "TRANSFER") {
        return $wallet->credit($transaction_request);
    }
    if($type == "DEBIT"){
        return $wallet->debit($transaction_request);
    }

    return true;

}

function verifyCustomer(User $user) : Customer
{
    Cache::forget($user->getCacheKey());
    if($user->stripeId() == null) {
        $customer = $user->createOrGetStripeCustomer();
    }else{
        $customer = $user->asStripeCustomer();
    }

    return $customer;
}

function convertCurrency($destinationCurrency, $amount)
{
    if ($destinationCurrency == "xaf" || $destinationCurrency == "XAF") {
        $amount = $amount * env("EUR_DEFAULT_XAF_VALUE", 655);
        $destinationCurrency = "EUR";
    }
    $currencyService = app(ICurrencyService::class);
    $currency = $currencyService->fetchCurrencyByCode($destinationCurrency);

    return $amount * ($currency["rate"] == null? 0 : $currency["rate"]);
}
