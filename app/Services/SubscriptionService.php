<?php

namespace App\Services;

use App\Dtos\Subscription as DtosSubscription;
use App\Events\SubscriptionCreated;
use Stripe\Price;
use Stripe\Stripe;
use App\Models\User;
use Stripe\Customer;
use Stripe\Subscription as StripeSubscription;
use Stripe\PaymentMethod;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use App\Models\SubscriptionAssignment;
use Stripe\Exception\ApiErrorException;
use App\Services\Contracts\IUserService;
use App\Http\Resources\PackageCollection;
use App\Services\Contracts\ISubscriptionService;
use Illuminate\Support\Facades\Log;

class SubscriptionService extends BaseService implements ISubscriptionService
{
    protected IUserService $userService;

    public function __construct(IUserService $userService)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $this->userService = $userService;
    }

    /**
     * Gets list of packages with all related prices for the given currency
     *
     * @param  string $currency
     * @return PackageCollection
     * @throws ApiErrorException
     */
    public function findPackagesByCurrency(string $currency): PackageCollection
    {
        $cache_key = 'packages:' . $currency;

        $packages = Cache::remember($cache_key, now()->addHour(), function () use ($currency) {
            return Price::search(
                [
                    "query" => "active:'true'",
                    "expand" => [
                        "data.product", // show product details
                        "data.currency_options.$currency.tiers", // show all available currencies per price
                    ]
                ]
            )->data;
        });

        return new PackageCollection($packages);
    }

    /**
     * @inheritDoc
     */
    public function isAssigned(int $subscription_id, int $user_id)
    {
        return SubscriptionAssignment::query()->where('subscription_id', '=', $subscription_id)
            ->where('user_id', '=', $user_id)->get()->count() > 0;
    }

    /**
     * @inheritDoc
     */
    public function createStripePaymentMethod(
        User $user,
        string $paymentType,
        array $cardDetails = [],
        array $address = []
    ): PaymentMethod {
        $stripeCustomerId = null;

        if ($user->hasStripeId()) {
            // add the user in stripe system
            $stripeCustomer = $user->asStripeCustomer();
            $stripeCustomerId = $stripeCustomer->id;
        }

        $billingDetails = [
            'email' => $user->stripeEmail(),
            'phone' => $user->stripePhone(),
            'address' => $address
        ];

        // only one of the contact details is needed to create a payment method. by default we choose email if not empty
        if ($user->stripeEmail()) {
            unset($billingDetails['phone']);
        } else {
            unset($billingDetails['email']);
        }

        // update the user birthday in stripe system
        if ($stripeCustomerId) {
            $customer = Customer::retrieve($stripeCustomerId);
            // Check if 'birthday' metadata already exists
            if (!isset($customer->metadata['birthday'])) {
                $customer->metadata['birthday'] = $user->birthday;
                $customer->save();
            }
        }
        switch ($paymentType) {
            case 'card':
                return PaymentMethod::create(
                    [
                        'type' => $paymentType,
                        'card' => $cardDetails,
                        'billing_details' => $billingDetails,
                    ]
                );
                break;
            case 'om':
                return PaymentMethod::create(
                    [
                        'type' => "customer_balance",
                        'billing_details' => $billingDetails,
                    ]
                );
                break;

            default:
                return null;
                break;
        }
    }

    /**
     * @inheritDoc
     */
    public function makePayment(
        User $user,
        string $product,
        string $price,
        int $qty,
        PaymentMethod $paymentMethod = null
    ): Subscription {
        $subscription = $user->newSubscription($product, [$price])
            ->quantity($qty)->create($paymentMethod);
        Log::info("SUBSCRIPTION MADE: SUBSCRIPTION CREATED", ["subscription" => $subscription]);
        $subscription_id = $subscription->id;
        Cache::forget($user->getCacheKey());

        /**
         * @var User
         */
        $user = $this->userService->findOneById($user->id);

        return $user->subscriptions->first(function ($subscription) use ($subscription_id) {
            return $subscription->id == $subscription_id;
        });
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionsOfUser(User $user)
    {
        // Fetch all paid subscriptions
        $subscriptions = $user->subscriptions->all();

        // The user may have also been assigned some subscriptions
        // So we fetch all the identifiers of these assigned subscriptions of the user too
        $assigned_subscriptions = $user->assignedSubscriptions->all();

        return array_merge($subscriptions, $assigned_subscriptions);
    }

    /**
     * @inheritDoc
     */
    public function getSponsoredSubscriptions(User $user)
    {
        $subscriptions = $user->subscriptions->reject(function ($subscription) {
            return SubscriptionAssignment::where('subscription_id', '=', $subscription->id)->get()->count() == 0;
        });

        return $subscriptions->all();
    }

    /**
     * @inheritDoc
     */
    public function assignSubscription(int $subscription_id, int $user_id)
    {
        $assignmentExists = SubscriptionAssignment::where('subscription_id', '=', $subscription_id)
            ->where('user_id', '=', $user_id)->get()->first() !== null;

        if (!$assignmentExists) {
            return (new SubscriptionAssignment([
                'subscription_id' => $subscription_id,
                'user_id' => $user_id
            ]))->saveOrFail();
        } else {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function denySubscription(int $subscription_id, int $user_id)
    {
        return (bool) SubscriptionAssignment::where('subscription_id', '=', $subscription_id)
            ->where('user_id', '=', $user_id)->delete();
    }

    /**
     * @inheritDoc
     */
    public function createCheckout(
        User $user,
        string $product_name,
        string $price_id,
        int $quantity,
        string $redirect_url
    ) {
        return $user->newSubscription($product_name, $price_id)
        ->quantity($quantity)
        ->checkout(
            [
                "success_url" => $redirect_url."/dashboard?payment_status=1",
                "cancel_url" => $redirect_url."/dashboard?payment_status=-1",
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function getModelObject(): Subscription
    {
        return new Subscription();
    }

    public function showSubscription($id): StripeSubscription
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        return $stripe->subscriptions->retrieve($id);
    }

    public function createSubscription(User $user, DtosSubscription $data): Subscription
    {
        $subscription =  $user->subscriptions()->create($data->jsonSerialize());
        Cache::forget($user->getCacheKey());
        return $subscription;
    }
}
