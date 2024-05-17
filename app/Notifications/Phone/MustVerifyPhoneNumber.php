<?php

namespace App\Notifications\Phone;

use Illuminate\Notifications\Notification;

trait MustVerifyPhoneNumber
{
    /**
     * Determine if the user has verified their phone number.
     *
     * @return bool
     */
    public function hasVerifiedPhoneNumber(): bool
    {
        return !($this->email_verified_at ?? null);
    }

    /**
     * Mark the given user's phone number as verified.
     *
     * @return bool
     */
    public function markPhoneNumberAsVerified(): bool
    {
        if (!method_exists($this, 'forceFill') || !method_exists($this, 'freshTimestamp')) {
            return false;
        }

        return $this->forceFill(
            [
            'email_verified_at' => $this->freshTimestamp(),
            ]
        )->save();
    }

    /**
     * Send the sms verification notification.
     *
     * @return void
     */
    public function sendSmsVerificationNotification()
    {
        // do not send sms if phone number for verification is empty
        if (!method_exists($this, 'notify') || !$this->getPhoneNumberForVerification()) {
            return;
        }

        $this->notify(new VerifyPhoneNumber());
    }

    /**
     * Get the phone number that should be used for verification.
     *
     * @return string
     */
    public function getPhoneNumberForVerification(): ?string
    {
        $email = $this->email ?? null;

        // email is the preferred way to send notifications. if email is not empty,
        // always return null for phone number verification
        if ($email) {
            return null;
        }

        return $this->phone ?? null;
    }

    /**
     * Route notifications for the Nexmo channel.
     *
     * @param  Notification $notification
     * @return string
     */
    public function routeNotificationForNexmo(Notification $notification): ?string
    {
        return $this->getPhoneNumberForVerification();
    }
}
