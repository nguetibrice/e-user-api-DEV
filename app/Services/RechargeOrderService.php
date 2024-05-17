<?php

namespace App\Services;

use App\Dtos\RechargeOrder as DtoRechargeOrder;
use App\Models\RechargeOrder;
use App\Services\Contracts\IRechargeOrderService;
use Illuminate\Support\Facades\Cache;

class RechargeOrderService extends BaseService implements IRechargeOrderService
{

    //recharge Orders section
    public function getRechargeOrders()
    {
        return Cache::remember('recharge_orders', now()->addHour(), function () {
            $recharge_orders = [];

            foreach (RechargeOrder::all() as $order) {
                $recharge_orders[] = $order;
            };

            return $recharge_orders;
        });
    }
    public function getRechargeOrder(int $id): RechargeOrder
    {
        // $order = $this->findOneById($id);
        $order = RechargeOrder::with("user")->find($id);
        return $order;
    }

    public function addRechargeOrder(DtoRechargeOrder $rechargeOrderDto): RechargeOrder
    {
        $data = $rechargeOrderDto->jsonSerialize();
        $data["status"] = 0;
        $order = new RechargeOrder($data);

        $this->insert($order); // Store the order

        Cache::forget('recharge_orders');

        return $order;
    }

    public function updateRechargeOrder(string $id, DtoRechargeOrder $rechargeOrderDto): RechargeOrder
    {
        $order = $this->findOneById($id);
        $data = $rechargeOrderDto->jsonSerialize();
        if ($data) {
            foreach ($data as $key => $value) {
                $order->{$key} = $value;
            }

            $this->update($order); // Update the currency

            Cache::forget('recharge_orders');
        }

        return $order;
    }

    public function deleteRechargeOrder(string $id)
    {
        $order = $this->findOneById($id);

        $this->delete($order); // Remove the currency

        Cache::forget('recharge_orders');
    }

    protected function getModelObject(): RechargeOrder
    {
        return new RechargeOrder();
    }
}
