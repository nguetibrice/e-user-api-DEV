<?php

namespace App\Listeners;

use App\Events\BaseDjedSyncEvent;
use App\Events\SubscriptionCreated;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Contracts\ILanguageService;
use App\Services\Contracts\IPriceService;
use App\Services\Contracts\ISubscriptionService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SubscriptionCreatedListener extends BaseDjedSyncListener
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
     * @param  \App\Events\SubscriptionCreated  $event
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
        $subscription = $this->subscriptionService->showSubscription($event->getModel()->stripe_id);
        $language_id = $subscription->items["data"][0]["price"]["product"];
        $language = $this->languageService->getLanguage($language_id);
        $user = User::find($event->getModel()->user_id);
        $type_bouquet = $this->getTypeBouquet($event->getModel()->quantity);
        $start_date = new DateTime();
        $start_date->setTimestamp($subscription->current_period_start);
        $end_date = new DateTime();
        $end_date->setTimestamp($subscription->current_period_end);

        $data = [
            "alias" => $user->alias,
            "type_bouquet" => $type_bouquet,
            "code_langue" => $language->getCode(),
            "nom_bouquet" => $language->getName(),
            "date_debut" => $start_date->format("Y-m-d"),
            "date_fin" => $end_date->format("Y-m-d"),
        ];
        Log::alert("data:".json_encode($data));
        return $data;
    }
}
