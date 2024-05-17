<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Contracts\ILanguageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    protected ILanguageService $languageService;
    public function __construct(ILanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * Handle the event.
     *
     * @param  WebhookReceived  $event
     * @return void
     */
    public function handle(WebhookReceived $event)
    {
        //
        Log::info("EVENT_RECEIVED: ".json_encode($event->payload));
        if ($event->payload['type'] === 'invoice.paid') {
            Log::info("CHECKOUT_COMPLETED: ".json_encode($event->payload));
            $session = $event->payload['data']['object'];
            if ($session['lines']['data'][0]['type'] == 'subscription') {
                $language_id = $session['lines']['data'][0]['plan']['product'];
                $language = $this->languageService->getLanguage($language_id);
                Log::info("PAYMENT_SESSION: ".json_encode($language));
                $user = User::where('stripe_id', $session['customer'])->first();
                if ($user) {
                    $user->subscriptions()->create([
                        'name' => $language->getName(),
                        'stripe_id' => $session['subscription'],
                        'stripe_status' => $session['status'] == 'paid' ? 'active' : 'inactive',
                        'stripe_price' => $session["subscription_details"]['metadata']["name"],
                        'quantity' => $session['lines']['data'][0]['quantity'],
                        'trial_ends_at' => null,
                        'ends_at' => null,
                    ]);
                }
            }
        }
    }
}
