<?php

namespace App\Services;

use App\Models\User;
use App\Exceptions\ModelException;
use Illuminate\Support\Facades\DB;
use App\Models\PersonalAccessToken;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Services\Contracts\IUserService;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Exceptions\ModelNotFoundException;

class UserService extends BaseService implements IUserService
{
    /**
     * @inheritDoc
     */
    public function getUserByAlias(string $alias): User|null
    {
        /** @var User $user */
        $user = $this->findOneBy('alias', $alias);

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function getUserByEmail(string $email): User|null
    {
        /** @var User $user */
        $user = $this->findOneBy('email', $email);

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function getUserByCip(string $cip): User|null
    {
        /** @var User $user */
        $user = $this->findOneBy('cip', $cip);

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function getUserByPhone(string $phone): User|null
    {
        /** @var User $user */
        $user = $this->findOneBy('phone', $phone);

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function getUserByStripeId(string $stripe_id): User|null
    {
        /** @var User $user */
        $user = $this->findOneBy('stripe_id', $stripe_id);

        return $user;
    }


    /**
     * @inheritDoc
     */
    public function destroySession(User $user): void
    {
        $result = $user->tokens()
            ->delete();

        if (!$result) {
            throw new ModelException('Failed to delete token');
        }
    }

    /**
     * @inheritDoc
     */
    public function createAccount(User $user): User
    {
        $this->insert($user);

        $user->createPin();

        // trigger event to send asynchronous email or sms notification to the user
        event(new Registered($user));

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function resentActivationCode(User $user): User
    {
        $user->createPin();

        //send new asynchronous notification
        event(new Registered($user));

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function generateUserTokenFromIp(User $user, string $ip): array
    {
        $token = $user->createToken($ip);

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $token->accessToken;

        return [
            'token' => [
                'value' => $token->plainTextToken,
                'type' => 'bearer',
                'expire_at' => $accessToken->expired_at
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function hasValidCredentials(string $alias, string $password): bool
    {
        $credentials = ['alias' => $alias, 'password' => $password];

        return Auth::attempt($credentials);
    }

    /**
     * @inheritDoc
     */
    public function getUserFromGuard(): User
    {
        $user = Auth::user();
        if (!$user) {
            throw new ModelNotFoundException('User cannot be found');
        }

        $this->setModel($user);

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function getLastActivationCode(User $user): int
    {
        $activationCode = intval($user->passwordResets->last()->token ?? null);
        if (!$activationCode) {
            throw new ModelNotFoundException('No activation code found');
        }

        return $activationCode;
    }

    /**
     * @inheritDoc
     */
    public function markAccountAsVerified(User $user): void
    {
        if (!$user->passwordResets()->delete()) {
            throw new ModelException('Failed to delete user pins');
        }

        if (!$user->markEmailAsVerified()) {
            throw new ModelException('Failed to mark user email or phone as verified');
        }
    }

    public function updateUser(User $user, array $attributes)
    {
        return $user->updateOrFail($attributes);
    }

    /**
     * @inheritDoc
     */
    public function destroyUserAccount(User $user)
    {
        $this->destroySession($user);
        return $user->deleteOrFail();
    }

    public function requestPasswordReset(string $email, string $reset_password_url)
    {
        $credentials = ['email' => $email];
        return Password::sendResetLink($credentials, function ($user, $token) use ($reset_password_url) {
            $user->notify(new ResetPassword($token, $reset_password_url));
        });
    }

    public function resetPassword(array $credentials)
    {
        return Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);

                $user->save();

                event(new PasswordReset($user));
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function findPackage(User $user)
    {
        $userPackage = DB::table('subscriptions')->where('user_id', $user->id)->get();

        if (!$userPackage) {
            throw new ModelException("élément introuvable");
        }

        return $userPackage;
    }

    /**
     * @inheritDoc
     */
    public function getGodchildren(User $user, int $subscription_id = null)
    {
        $subscriptionAssignments = $user->subscriptionAssignments();
        if ($subscription_id) {
            $subscriptionAssignments = $subscriptionAssignments->where('subscription_id', '==', $subscription_id);
        }

        return $subscriptionAssignments->get()->pluck('user');
    }


    /**
     * @inheritDoc
     */
    protected function getModelObject(): User
    {
        return new User();
    }
}
