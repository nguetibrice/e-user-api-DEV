<?php

namespace App\Listeners;

use App\Dtos\Language;
use App\Events\BaseDjedSyncEvent;
use App\Events\LanguageCreatedOrUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;

class LanguageCreatedOrUpdatedListener extends BaseDjedSyncListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        $language = $event->getModel();
        $action = $event->getAction();
        $data = [
            "code" => $language->getCode(),
            "description" => $language->getDescription(),
            "status" => $language->getStatus(),
            "name" => $language->getName(),
        ];
        $redisData = [
            'action' => $action,
            'model' => "Language",
            'data' => $data,
            'datetime' => now(),
        ];
        return $redisData;
    }
}
