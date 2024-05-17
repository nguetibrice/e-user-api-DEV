<?php

namespace App\Notifications\Phone;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class VerifyPhoneNumber extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['nexmo'];
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     *
     * @param  mixed $notifiable
     * @return NexmoMessage
     */
    public function toNexmo($notifiable): NexmoMessage
    {
        $pin = $notifiable->passwordResets->first()->token;

        $content = Lang::get('Thank you for signing up.') . PHP_EOL;
        $content .= Lang::get('Your six-digit code is') . " $pin" . PHP_EOL;
        $content .= Lang::get('If you did not create an account, no further action is required.') . PHP_EOL;
        $content .= Lang::get('Regards') . '.' . PHP_EOL;
        $content .= config('app.name');

        return (new NexmoMessage())->content($content);
    }
}
