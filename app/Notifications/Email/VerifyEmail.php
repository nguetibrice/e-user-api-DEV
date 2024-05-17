<?php

namespace App\Notifications\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class VerifyEmail extends \Illuminate\Auth\Notifications\VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $mailMessage = new MailMessage();
        $mailMessage->markdown('emails.signup');

        $pin = $notifiable->passwordResets->first()->token;

        return $mailMessage->subject(Lang::get('Verify your email'))
            ->line(Lang::get('Thank you for signing up.'))
            ->line(Lang::get('Your six-digit code is') . " $pin")
            ->line(Lang::get('If you did not create an account, no further action is required.'));
    }
}
