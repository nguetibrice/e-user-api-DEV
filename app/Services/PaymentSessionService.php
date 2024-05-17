<?php

namespace App\Services;

use App\Dtos\PaymentSession as DtoPaymentSession;
use App\Models\PaymentSession;
use App\Services\Contracts\IPaymentSessionService;
use Illuminate\Support\Facades\Cache;

class PaymentSessionService extends BaseService implements IPaymentSessionService
{

    //subscription Orders section
    public function getPaymentSessions()
    {
        return Cache::remember('payment_sessions', now()->addHour(), function () {
            $payment_sessions = [];

            foreach (PaymentSession::all() as $session) {
                $payment_sessions[] = $session;
            };

            return $payment_sessions;
        });
    }
    public function getPaymentSession(int $id): PaymentSession
    {
        $session = $this->findOneById($id);
        return $session;
    }
    public function getPaymentSessionByReference(string $ref): PaymentSession
    {
        $session = $this->findOneBy("reference",$ref);
        return $session;
    }

    public function addPaymentSession(DtoPaymentSession $subscriptionOrderDto): PaymentSession
    {
        $data = $subscriptionOrderDto->jsonSerialize();
        $session = new PaymentSession($data);

        $this->insert($session); // Store the session

        Cache::forget('payment_sessions');

        return $session;
    }

    public function updatePaymentSession(string $id, DtoPaymentSession $subscriptionOrderDto): PaymentSession
    {
        $session = $this->findOneById($id);
        $data = $subscriptionOrderDto->jsonSerialize();
        if ($data) {
            foreach ($data as $key => $value) {
                $session->{$key} = $value;
            }

            $this->update($session); // Update the currency

            Cache::forget('payment_sessions');
        }

        return $session;
    }

    public function deletePaymentSession(string $id)
    {
        $session = $this->findOneById($id);

        $this->delete($session); // Remove the currency

        Cache::forget('payment_sessions');
    }

    protected function getModelObject(): PaymentSession
    {
        return new PaymentSession();
    }
}
