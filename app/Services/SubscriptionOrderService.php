<?php

namespace App\Services;

use App\Dtos\SubscriptionOrder as DtoSubscriptionOrder;
use App\Models\SubscriptionOrder;
use App\Services\Contracts\ISubscriptionOrderService;
use Illuminate\Support\Facades\Cache;

class SubscriptionOrderService extends BaseService implements ISubscriptionOrderService
{

    //subscription Orders section
    public function getSubscriptionOrders()
    {
        return Cache::remember('subscription_orders', now()->addHour(), function () {
            $subscription_orders = [];

            foreach (SubscriptionOrder::all() as $order) {
                $subscription_orders[] = $order;
            };

            return $subscription_orders;
        });
    }
    public function getSubscriptionOrder(int $id): SubscriptionOrder
    {
        // $order = $this->findOneById($id);
        $order = SubscriptionOrder::with("user")->find($id);
        return $order;
    }

    public function addSubscriptionOrder(DtoSubscriptionOrder $subscriptionOrderDto): SubscriptionOrder
    {
        $data = $subscriptionOrderDto->jsonSerialize();
        $data["status"] = 0;
        $order = new SubscriptionOrder($data);

        $this->insert($order); // Store the order

        Cache::forget('subscription_orders');

        return $order;
    }

    public function updateSubscriptionOrder(string $id, DtoSubscriptionOrder $subscriptionOrderDto): SubscriptionOrder
    {
        $order = $this->findOneById($id);
        $data = $subscriptionOrderDto->jsonSerialize();
        if ($data) {
            foreach ($data as $key => $value) {
                $order->{$key} = $value;
            }

            $this->update($order); // Update the currency

            Cache::forget('subscription_orders');
        }

        return $order;
    }

    public function deleteSubscriptionOrder(string $id)
    {
        $order = $this->findOneById($id);

        $this->delete($order); // Remove the currency

        Cache::forget('subscription_orders');
    }

    protected function getModelObject(): SubscriptionOrder
    {
        return new SubscriptionOrder();
    }
}
