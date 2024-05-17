<?php

namespace App\Events;

use App\Models\SubscriptionAssignment;

class SubscriptionAssigned extends BaseDjedSyncEvent
{

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($subscriptionAssignment)
    {
        parent::__construct($subscriptionAssignment);
    }
}
