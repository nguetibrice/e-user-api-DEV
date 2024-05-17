<?php

namespace App\Listeners;

use App\Events\BaseDjedSyncEvent;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Contracts\ILanguageService;
use App\Services\Contracts\ISubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SubscriptionAssignedListener extends BaseDjedSyncListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    protected ISubscriptionService $subscriptionService;
    protected ILanguageService $languageService;
    public function __construct(
        ISubscriptionService $subscriptionService,
        ILanguageService $languageService
    )
    {
        $this->subscriptionService = $subscriptionService;
        $this->languageService = $languageService;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(BaseDjedSyncEvent $event)
    {
        $data = $this->getDataFromEvent($event);
        $redisData = $this->prepareDataForRedis($event, $data);
        $this->publishData($event, $redisData);
    }

    public function getDataFromEvent(BaseDjedSyncEvent $event): array
    {
        $subscription_assignment = $event->getModel();
        $subscription = Subscription::find($subscription_assignment->subscription_id);
        $destinataire = User::find($subscription_assignment->user_id);
        $user = User::find($subscription->user_id);
        $_subscription = $this->subscriptionService->showSubscription($subscription->stripe_id);
        $language_id = $_subscription->items["data"][0]["price"]["product"];
        $language = $this->languageService->getLanguage($language_id);
        $data = [
            "alias" => $user->alias,
            "type_bouquet" => $this->getTypeBouquet($subscription->quantity),
            "nom_bouquet" => $language->getName(),
            "destinataire" => $destinataire->alias,
        ];
        return $data;
    }
}
