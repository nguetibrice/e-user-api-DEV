<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BaseDjedSyncEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected const ACTION_CREATED = 'created';
    protected const ACTION_UPDATED = 'updated';
    protected string $action;

    /**
     * Model
     *
     */
    protected $model;
    /**
     * date time format should be Y-m-d H:is
     * @var string
     */
    protected string $datetime;


    protected function __construct($model)
    {
        $this->model = $model;
        if ($model instanceof Model) {
            $this->action = $model->wasRecentlyCreated ? self::ACTION_CREATED : self::ACTION_UPDATED;
        }
        $this->datetime = now()->format('Y-m-d H:i:s');
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel(strtolower(env("APP_ENV")). '-e-user-api-channel');
    }

     /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

     /**
     * @return self
     */
    public function setAction($action): string
    {
        return $this->action = $action;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getDatetime(): string
    {
        return $this->datetime;
    }
}
