<?php

namespace App\Wallet\Contracts;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\ModelNotFoundException;
use App\Wallet\BaseWalletRequest;
use App\Wallet\BaseWalletResponse;
use App\Wallet\Stripe\Requests\TransactionRequest;

interface WalletContract
{
    /**
     * Credit Wallet Balance
     *
     * @template T as BaseWalletRequest
     * @param T $req
     * @return BaseWalletResponse
     */
    public function credit($req): BaseWalletResponse;


    /**
     * Debit Wallet Balance
     *
     * @template T as BaseWalletRequest
     * @param T $req
     * @return BaseWalletResponse
     */
    public function debit($req): BaseWalletResponse;

    /**
     * Get Wallet Balance
     *
     * @template T as BaseWalletRequest
     * @param T $req
     * @return BaseWalletResponse
     */
    public function getBalance($req): BaseWalletResponse;
}
