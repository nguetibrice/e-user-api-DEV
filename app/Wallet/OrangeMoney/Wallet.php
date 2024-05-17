<?php

namespace App\Wallet\OrangeMoney;

use App\Services\Contracts\IOrangeMoneyService;
use App\Wallet\BaseWalletResponse;
use App\Wallet\Contracts\WalletContract;
use App\Wallet\OrangeMoney\Requests\TransactionRequest;
use App\Wallet\OrangeMoney\Responses\TransactionResponse;
use Exception;
use Stripe\Customer;

class Wallet implements WalletContract
{
    protected IOrangeMoneyService $omService;
    public function __construct(IOrangeMoneyService $omService)
    {
        $this->omService = $omService;
    }
    /**
     * Credit Customer Orange Money account
     *
     * @param TransactionRequest $request
     * @return BaseWalletResponse
     */
    public function credit($request): BaseWalletResponse
    {
        $response = new BaseWalletResponse();
        $response->setError(new Exception("Unable to process this request", 400));
        return $response;
    }

    /**
     * Get Payment Link for Orange money payment
     *
     * @param TransactionRequest $request
     * @return BaseWalletResponse
     */
    public function debit($request): BaseWalletResponse
    {
        try {
            $data = [
                "currency" => strtoupper($request->getCurrency()),
                "order_id" => $request->getRef(),
                "amount" => $request->getAmount(),
                "return_url" => env('EUSER_APP_URL')."/dashboard?status=1",
                "cancel_url" => env('EUSER_APP_URL')."/dashboard?status=-1",
                "notif_url" => url('/')."/api/v1/payment/callback?uuid=". $request->getRef(),
                "lang" => "fr",
                "reference" =>"DJEDSO".time(),
            ];
            $results =  $this->omService->pay($data);
            if (isset($results['error'])) {
                $response = new BaseWalletResponse();
                $response->setError(new Exception(json_encode($results['error']), 500));
                return $response;
            }
            return $this->getBalance($request, $results["data"]["payment_url"], $results["data"]["notif_token"]);
        } catch (\Throwable $th) {
            $error =  new BaseWalletResponse() ;
            $error->setError($th);
            return $error;
        }
    }

    /**
     * Get Customer Stripe balance
     *
     * @param TransactionRequest $request
     * @return BaseWalletResponse
     */
    public function getBalance($request, $payment_url = null, $notif_token = null): BaseWalletResponse
    {
        try {
            $customer = Customer::retrieve($request->getCustomerId());
            return new TransactionResponse(
                $payment_url,
                $notif_token,
                $customer["id"],
                $customer["currency"],
                $customer["balance"]
            );
        } catch (\Throwable $th) {
            $error =  new BaseWalletResponse();
            $error->setError($th);
            return $error;
        }
    }
}
