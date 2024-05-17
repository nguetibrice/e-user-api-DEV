<?php

namespace App\Wallet\Stripe;

use App\Dtos\TransactionHistory;
use App\Models\User;
use App\Services\Contracts\ITransactionHistoryService;
use App\Services\Contracts\IUserService;
use App\Wallet\BaseWalletRequest;
use App\Wallet\BaseWalletResponse;
use App\Wallet\Contracts\WalletContract;
use App\Wallet\Stripe\Requests\TransactionRequest;
use Illuminate\Foundation\Auth\User as AuthUser;
use Laravel\Cashier\Cashier;
use Stripe\Customer;
use Stripe\CustomerBalanceTransaction;

class Wallet implements WalletContract
{

    protected ITransactionHistoryService $transactionHistoryService;
    protected IUserService $userService;

    public function __construct(
        ITransactionHistoryService $transactionHistoryService,
        IUserService $userService
    )
    {
        $this->transactionHistoryService = $transactionHistoryService;
        $this->userService = $userService;
    }
    /**
     * Credit Customer Stripe account
     *
     * @param TransactionRequest $request
     * @return BaseWalletResponse
     */
    public function credit($request): BaseWalletResponse
    {
        try {
            Customer::createBalanceTransaction(
                $request->getCustomerId(),
                ['amount' => -$request->getAmount(), 'currency' => $request->getCurrency()]
            );

            $user = $this->userService->findOneBy("stripe_id", $request->getCustomerId());
            $this->createLocalTransaction(
                $user,
                $request->getAmount(),
                $request->getCurrency(),
                "CREDIT",
                $request->getMotif()
            );
            return $this->getBalance($request);
        } catch (\Throwable $th) {
            $error =  new BaseWalletResponse() ;
            $error->setError($th);
            return $error;
        }
    }

    /**
     * Debit Customer Stripe Account
     *
     * @param TransactionRequest $request
     * @return BaseWalletResponse
     */
    public function debit($request): BaseWalletResponse
    {
        try {
            Customer::createBalanceTransaction(
                $request->getCustomerId(),
                ['amount' => $request->getAmount(), 'currency' => $request->getCurrency()]
            );
            $user = $this->userService->findOneBy("stripe_id", $request->getCustomerId());
            $this->createLocalTransaction(
                $user,
                $request->getAmount(),
                $request->getCurrency(),
                "DEBIT",
                $request->getMotif()
            );
            return $this->getBalance($request);
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
    public function getBalance($request): BaseWalletResponse
    {
        try {
            $customer = Customer::retrieve($request->getCustomerId());
            return new BaseWalletResponse($customer["id"], $customer["currency"], $customer["balance"]);
        } catch (\Throwable $th) {
            $error =  new BaseWalletResponse() ;
            $error->setError($th);
            return $error;
        }
    }

    private function createLocalTransaction($user, $amount, $currency, $type, $motif=null)
    {
        $transactionDto = new TransactionHistory(
            $user->id,
            $type,
            $amount,
            strtoupper($currency),
            $motif, //format text for proper motif
            // $response->get
        );
        $this->transactionHistoryService->addTransactionHistory($transactionDto);
        return true;
    }
}
