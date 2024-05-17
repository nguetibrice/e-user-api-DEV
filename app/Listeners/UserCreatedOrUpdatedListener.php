<?php

namespace App\Listeners;

use App\Events\BaseDjedSyncEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserCreatedOrUpdatedListener extends BaseDjedSyncListener
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
        $data = [
            'id' => $event->getModel()->id,
            'first_name'=> $event->getModel()->first_name,
            'last_name' => $event->getModel()->last_name,
            'alias' => $event->getModel()->alias,
            'email' => $event->getModel()->email,
            'email_verified_at' => $event->getModel()->email_verified_at,
            'password' => $event->getModel()->password,
        ];
        return $data;
    }
}
