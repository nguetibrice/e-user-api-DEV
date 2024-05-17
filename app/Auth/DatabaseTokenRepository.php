<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\DatabaseTokenRepository as TokenRepository;

class DatabaseTokenRepository extends TokenRepository
{
    public function create(CanResetPassword $user)
    {
        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $record = $user->createPin();
        $record->token = $this->hasher->make($token);
        $record->save();

        return $token;
    }

    protected function deleteExisting(CanResetPassword $user)
    {
        return $this->getTable()->where('contact', $user->getEmailForPasswordReset())->delete();
    }

    protected function getPayload($email, $token)
    {
        return ['contact' => $email, 'token' => $this->hasher->make($token)];
    }

    public function exists(CanResetPassword $user, $token)
    {
        $record = (array) $this->getTable()->where(
            'contact',
            $user->getEmailForPasswordReset()
        )->first();

        return $record &&
            !$this->tokenExpired($record['created_at']) &&
            $this->hasher->check($token, $record['token']);
    }

    public function recentlyCreatedToken(CanResetPassword $user)
    {
        $record = (array) $this->getTable()->where(
            'contact',
            $user->getEmailForPasswordReset()
        )->first();

        return $record && $this->tokenRecentlyCreated($record['created_at']);
    }
}
