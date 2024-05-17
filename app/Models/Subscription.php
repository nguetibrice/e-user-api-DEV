<?php

namespace App\Models;

use App\Events\SubscriptionCreated;
use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    use BaseModel;

    protected $dispatchesEvents  = [
        "created" => SubscriptionCreated::class
    ];
}
