<?php

namespace App\Http\Controllers;

use App\Dtos\Subscription as SubscriptionDTO;
use App\Models\User;
use App\Services\Contracts\ILanguageService;
use App\Services\Contracts\ISubscriptionService;
use App\Services\Contracts\IUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Log;

class StripeWebhookController extends WebhookController
{
    protected ILanguageService $languageService;
    protected IUserService $userService;
    protected ISubscriptionService $subscriptionService;
    public function __construct(
        ILanguageService $languageService,
        IUserService $userService,
        ISubscriptionService $subscriptionService
    )
    {
        $this->languageService = $languageService;
        $this->userService = $userService;
        $this->subscriptionService = $subscriptionService;
    }
    public function handleWebhook(Request $request)
    {
        try {
            $payload = $request->getContent();
            $event = json_decode($payload, true);
            Log::info("EVENT_RECEIVED: ".json_encode($event));
            if ($event['type'] === 'invoice.paid') {
                Log::info("CHECKOUT_COMPLETED: ".json_encode($event));
                $session = $event['data']['object'];
                if ($session['lines']['data'][0]['type'] == 'subscription') {
                    $language_id = $session['lines']['data'][0]['plan']['product'];
                    $language = $this->languageService->getLanguage($language_id);
                    Log::info("PAYMENT_SESSION: ".json_encode($language));
                    $user = $this->userService->getUserByStripeId($session['customer']);
                    if ($user) {
                        $subscription_dto = new SubscriptionDTO(
                            $session['subscription'],
                            $language->getName(),
                            $session["subscription_details"]['metadata']["name"],
                            $session['lines']['data'][0]['quantity'],
                            $session['status'] == 'paid' ? 'active' : 'inactive'
                        );
                        $subscription = $this->subscriptionService->createSubscription($user, $subscription_dto);
                        Log::info("SUBSCRIPTION_CREATED: ".json_encode($subscription));
                    }
                }
            }
            return response("OK");
        } catch (\Throwable $th) {
            Log::error(
                "SOMETHING WENT WRONG:".
                json_encode([
                    "exception" => $th->getMessage(),
                    "code" => $th->getCode(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                    "trace" => $th->getTrace(),
                ])
            );
            return response("FAILED", 400);
        }

    }

}
