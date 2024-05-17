<?php

namespace App\Services\Contracts;

use App\Models\User;
use App\Exceptions\ModelException;
use App\Exceptions\ModelNotFoundException;

interface IUserService extends IBaseService
{
    /**
     * Finds a user by its alias
     *
     * @param string $alias User alias
     * @return User|null
     * @throws ModelNotFoundException Throws <b>ModelNotFoundException</b> exception if alias cannot be found
     */
    public function getUserByAlias(string $alias): User|null;

    /**
     * Finds a user by its alias
     *
     * @param string $alias User alias
     * @return User|null
     * @throws ModelNotFoundException Throws <b>ModelNotFoundException</b> exception if email cannot be found
     */
    public function getUserByEmail(string $email): User|null;

    /**
     * Finds a user by its Stripe ID
     *
     * @param string $stripe_id Stripe Customer Id
     * @return User|null
     * @throws ModelNotFoundException Throws <b>ModelNotFoundException</b> exception if email cannot be found
     */
    public function getUserByStripeId(string $stripe_id): User|null;

    /**
     * Finds a user by its CIP
     *
     * @param string $cip Customer CIP
     * @return User|null
     * @throws ModelNotFoundException Throws <b>ModelNotFoundException</b> exception if email cannot be found
     */
    public function getUserByCip(string $cip): User|null;

    /**
     * Finds a user by its Phone Number
     *
     * @param string $phone Customer phone
     * @return User|null
     * @throws ModelNotFoundException Throws <b>ModelNotFoundException</b> exception if email cannot be found
     */
    public function getUserByPhone(string $phone): User|null;


    /**
     * Finds a user using auth guard
     *
     * @return User
     * @throws ModelNotFoundException Throws <b>ModelNotFoundException</b> exception in case of empty result
     */
    public function getUserFromGuard(): User;

    /**
     * Invalidates user session
     *
     * @throws ModelException
     */
    public function destroySession(User $user): void;

    /**
     * Creates an account and sends a pin to the user via email or sms in order to complete his registration
     *
     * @param User $user
     * @return User
     * @throws ModelException
     */
    public function createAccount(User $user): User;

    /**
     * Generates a bearer token
     *
     * @param User $user User model
     * @param string $ip User ip
     * @return array{token: array{value: string, type: string, expire_at: string}}
     * @throws ModelNotFoundException
     */
    public function generateUserTokenFromIp(User $user, string $ip): array;

    /**
     * Checks if the provided credentials match with our records
     *
     * @param string $alias
     * @param string $password
     * @return bool
     */
    public function hasValidCredentials(string $alias, string $password): bool;

    /**
     * Deletes all activation codes generated for the user and mark its account as verified
     *
     * @param User $user
     * @return void
     * @throws ModelException
     */
    public function markAccountAsVerified(User $user): void;

    /**
     * Finds user last activation code
     *
     * @param User $user
     * @return int
     * @throws ModelNotFoundException
     */
    public function getLastActivationCode(User $user): int;

    /**
     * resends a pin to the user via email or sms if he not receive a first notification
     *
     * @param User $user
     * @return User
     * @throws ModelException
     */
    public function resentActivationCode(User $user): user;

    /**
     * @return string
     */
    public function requestPasswordReset(string $email, string $reset_password_url);

    /**
     * @return string
     */
    public function resetPassword(array $credentials);

    /**
     * Gets all the users sponsored by a specified user.
     *
     * If `subscription_id` is set, then it gets all godchildren
     * of a user for a specific subscription.
     *
     * @return User[]
     */
    public function getGodchildren(User $user, int $subscription_id = null);
}
