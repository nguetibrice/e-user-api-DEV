<?php

namespace App\Services\Contracts;

use App\Dtos\Subscription as DtosSubscription;
use App\Http\Resources\PackageCollection;
use App\Models\User;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Subscription;
use Stripe\Exception\ApiErrorException;
use Stripe\Subscription as StripeSubscription;
use Stripe\PaymentMethod;

interface ISubscriptionService extends IBaseService
{
    /**
     * Gets list of packages saved in stripe database
     *
     * @param string $currency
     * @return PackageCollection
     */
    public function findPackagesByCurrency(string $currency): PackageCollection;

    /**
     * Determines whether a given subscription is assigned to a specified user
     *
     * @return bool
     */
    public function isAssigned(int $subscription_id, int $user_id);

    /**
     * Creates a payment method for the user
     *
     * @param User $user
     * @param string $paymentType
     * @param array{number: string, exp_month: int, exp_year: int, cvc: string} $cardDetails
     * @param array{
     *   city: string, country: string, line1: string, line2:string, postal_code:string, state: string
     * } $address
     * @return PaymentMethod
     * @throws ApiErrorException
     */
    public function createStripePaymentMethod(
        User $user,
        string $paymentType,
        array $cardDetails = [],
        array $address = []
    ): PaymentMethod;

    /**
     * Makes a payment through stripe system
     *
     * @param User $user
     * @param string $product the product name
     * @param string $price the price identifier
     * @param int $qty the quantity
     * @param PaymentMethod $paymentMethod
     * @return Subscription
     * @throws IncompletePayment
     */
    public function makePayment(
        User $user,
        string $product,
        string $price,
        int $qty,
        PaymentMethod $paymentMethod = null
    ): Subscription;

    /**
     * Gets the all the subscriptions of a given user.
     *
     * Retrieves both the paid and assigned subscriptions
     * of a given user
     *
     * @return Subscription[]
     */
    public function getSubscriptionsOfUser(User $user);

    /**
     * Gets the all the subscriptions sponsored by a given user to their
     * godchildren.
     *
     * @return Subscription[]
     */
    public function getSponsoredSubscriptions(User $user);

    /**
     * Assigns a given subscription to a given user
     *
     * @return bool
     */
    public function assignSubscription(int $subscription_id, int $user_id);

    /**
     * Denies a given user access to a given subscription
     *
     * @return bool
     */
    public function denySubscription(int $subscription_id, int $user_id);

    /**
     * Generate Payment checkout Link
     *
     * @param User $user
     * @param string $product_name
     * @param string $price_id
     * @param integer $quantity
     * @param string $redirect_url
     * @return
     */
     public function createCheckout(
        User $user,
        string $product_name,
        string $price_id,
        int $quantity,
        string $redirect_url
    );

    /**
     * Get Single Subscription
     *
     * @param string $id
     * @return void
     */
    public function showSubscription(string $id): StripeSubscription;

    /**
     * create user Single Subscription
     *
     * @param User $user
     * @param array $data
     * @return Subscription
     */
    public function createSubscription(User $user, DtosSubscription $data): Subscription;



}
