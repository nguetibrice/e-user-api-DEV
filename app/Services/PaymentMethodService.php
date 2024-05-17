<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\PaymentMethodCurrency;
use Illuminate\Support\Facades\Cache;
use App\Services\Contracts\IPaymentMethodService;

class PaymentMethodService extends BaseService implements IPaymentMethodService
{
    public function getPaymentMethods()
    {
        return Cache::remember('payment_methods', now()->addHour(), function () {
            $payment_methods = [];

            foreach (PaymentMethod::with("currencies")->get() as $payment_method) {
                $payment_methods[] = $payment_method;
            };

            return $payment_methods;
        });
    }

    public function addPaymentMethod(array $data): PaymentMethod
    {
        $payment_method = new PaymentMethod($data);

        $this->insert($payment_method); // Store the payment_method
        foreach ($data["currencies"] as $currency) {
            $payment_method_currency = new PaymentMethodCurrency();
            $payment_method_currency->currency_id = $currency;
            $payment_method_currency->payment_method_id = $payment_method->id;
            $payment_method_currency->save();
        }

        Cache::forget('payment_methods');

        return $payment_method;
    }

    public function updatePaymentMethod(string $id, array $data): PaymentMethod
    {
        $payment_method = $this->findOneById($id);

        if ($data) {
            $currencies = $data["currencies"];
            unset($data["currencies"]);
            foreach ($data as $key => $value) {
                $payment_method->{$key} = $value;
            }

            $this->update($payment_method); // Update the payment_method
            PaymentMethodCurrency::where("payment_method_id", $payment_method->id)->delete();
            foreach ($currencies as $currency) {
                $payment_method_currency = new PaymentMethodCurrency();
                $payment_method_currency->currency_id = $currency;
                $payment_method_currency->payment_method_id = $payment_method->id;
                $payment_method_currency->save();
            }
            Cache::forget('payment_methods');
        }

        return $payment_method;
    }

    public function deletePaymentMethod(string $id)
    {
        $payment_method = $this->findOneById($id);
        PaymentMethodCurrency::where("payment_method_id", $payment_method->id)->delete();
        $this->delete($payment_method); // Remove the payment_method

        Cache::forget('payment_methods');
    }
    public function findPaymentMethod(string $id)
    {
        return PaymentMethod::with("currencies")->find($id);
    }

    protected function getModelObject(): PaymentMethod
    {
        return new PaymentMethod();
    }
}
