<?php

namespace App\Events;

use Laravel\Cashier\Subscription;

class SubscriptionCreated extends BaseDjedSyncEvent
{

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        $subscription
    )
    {
        parent::__construct($subscription);
    }


}
