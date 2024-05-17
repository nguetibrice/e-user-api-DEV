<?php

use App\Models\User;
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

    if ($type == "CREDIT") {
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
    if($user->stripeId() == null) {
        $customer = $user->createOrGetStripeCustomer();
    }else{
        $customer = $user->asStripeCustomer();
    }

    return $customer;
}

function convertCurrency($initialCurrency, $targetCurrency, $amount)
{
    // return Currency::convert()
    //     ->from(strtoupper($initialCurrency))
    //     ->to(strtoupper($targetCurrency))
    //     ->round(2)
    //     ->amount((float) $amount)
    //     ->withOptions(['query' => ['access_key' => env('CURRENCY_API_ID')]])
    //     ->throw()
    //     ->get();
}
