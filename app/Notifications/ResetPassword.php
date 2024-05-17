<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Lang;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * The url of the password reset form.
     *
     * @var string
     */
    public $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token, $url)
    {
        $this->token = $token;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = $this->url . '?token=' . $this->token . '&email=' . $notifiable->email;

        return (new MailMessage())
            ->subject(Lang::get('Reset Password Notification'))
            ->line(Lang::get(
                'You are receiving this email because we received a password reset request for your account.'
            ))
            ->action(Lang::get('Reset Password'), $url)
            ->line(Lang::get(
                'This password reset token will expire in :count minutes.',
                ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')]
            ))
            ->line(Lang::get('If you did not request a password reset, no further action is required.'));
    }
}
