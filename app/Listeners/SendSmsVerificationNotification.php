<?php

namespace App\Listeners;

use App\Notifications\Phone\MustVerifyPhoneNumber;
use Illuminate\Auth\Events\Registered;

class SendSmsVerificationNotification
{
    /**
     * Handle the event.
     *
     * @param  Registered $event
     * @return void
     */
    public function handle(Registered $event)
    {
        if ($event->user instanceof MustVerifyPhoneNumber && !$event->user->hasVerifiedPhoneNumber()) {
            $event->user->sendSmsVerificationNotification();
        }
    }
}
