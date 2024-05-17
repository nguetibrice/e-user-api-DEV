<?php

namespace App\Listeners;

use App\Events\BaseDjedSyncEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;
use Log;

abstract class BaseDjedSyncListener
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
        if ($data != null) {
            $redisData = $this->prepareDataForRedis($event, $data);
            Log::alert("data: " . json_encode($redisData));
            $this->publishData($event, $redisData);
        }
    }

    public abstract function getDataFromEvent(BaseDjedSyncEvent $event): array;

    protected function prepareDataForRedis(BaseDjedSyncEvent $event, array $data): array
    {
        $redisData = [
            'action' => $event->getAction(),
            'model' => class_basename($event->getModel()),
            'data' => $data,
            'datetime' => $event->getDatetime(),
        ];
        return $redisData;
    }

    protected function publishData(BaseDjedSyncEvent $event, array $data)
    {
        Redis::publish($event->broadcastOn()->name, json_encode($data));
    }

    protected function getTypeBouquet($quantity): string
    {
        switch ($quantity) {
            case 1:
                $type_bouquet = "Individuel";
                break;
            case 4:
                $type_bouquet = "Petite famille";
                break;
            case 7:
                $type_bouquet = "Famille moyenne";
                break;
            case 10:
                $type_bouquet = "Grande famille";
                break;
            case 50:
                $type_bouquet = "Association";
                break;
            case 500:
                $type_bouquet = "Communauté";
                break;
            case 5000:
                $type_bouquet = "Grande communauté";
                break;
            case 3000:
                $type_bouquet = "ecole";
                break;

            default:
                $type_bouquet = "Spécial";
                break;
        }

        return $type_bouquet;
    }
}
