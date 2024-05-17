<?php

namespace App\Providers;

use App\Events\LanguageCreatedOrUpdated;
use App\Events\SubscriptionAssigned;
use App\Events\SubscriptionCreated;
use App\Events\UserCreatedOrUpdated;
use App\Listeners\LanguageCreatedOrUpdatedListener;
use App\Listeners\SendSmsVerificationNotification;
use App\Listeners\StripeEventListener;
use App\Listeners\SubscriptionAssignedListener;
use App\Listeners\SubscriptionCreatedListener;
use App\Listeners\UserCreatedOrUpdatedListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            SendSmsVerificationNotification::class,
            // StripeEventListener::class,
        ],
        UserCreatedOrUpdated::class => [
            UserCreatedOrUpdatedListener::class
        ],
        SubscriptionCreated::class => [
            SubscriptionCreatedListener::class
        ],
        SubscriptionAssigned::class => [
            SubscriptionAssignedListener::class
        ],
        LanguageCreatedOrUpdated::class => [
            LanguageCreatedOrUpdatedListener::class
        ],

    ];


    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
