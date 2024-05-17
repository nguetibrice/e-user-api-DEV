<?php

namespace App\Services;

use App\Dtos\TransactionHistory as DtoTransactionHistory;
use App\Models\TransactionHistory;
use App\Services\Contracts\ITransactionHistoryService;
use Illuminate\Support\Facades\Cache;

class TransactionHistoryService extends BaseService implements ITransactionHistoryService
{

    //subscription Orders section
    public function getTransactionHistories()
    {
        return Cache::remember('transaction_histories', now()->addHour(), function () {
            $transaction_histories = [];

            foreach (TransactionHistory::all() as $order) {
                $transaction_histories[] = $order;
            };

            return $transaction_histories;
        });
    }
    public function getTransactionHistory(int $id): TransactionHistory
    {
        $order = TransactionHistory::with("user")->find($id);
        return $order;
    }

    public function addTransactionHistory(DtoTransactionHistory $transactionHistoriesDto): TransactionHistory
    {
        $data = $transactionHistoriesDto->jsonSerialize();
        $order = new TransactionHistory($data);

        $this->insert($order); // Store the order

        Cache::forget('transaction_histories');

        return $order;
    }

    public function updateTransactionHistory(string $id, DtoTransactionHistory $transactionHistoriesDto): TransactionHistory
    {
        $order = $this->findOneById($id);
        $data = $transactionHistoriesDto->jsonSerialize();
        if ($data) {
            foreach ($data as $key => $value) {
                $order->{$key} = $value;
            }

            $this->update($order); // Update the currency

            Cache::forget('transaction_histories');
        }

        return $order;
    }

    public function deleteTransactionHistory(string $id)
    {
        $order = $this->findOneById($id);

        $this->delete($order); // Remove the currency

        Cache::forget('transaction_histories');
    }

    protected function getModelObject(): TransactionHistory
    {
        return new TransactionHistory();
    }
}
