<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Lang;
use App\Services\Contracts\IUserService;
use Illuminate\Support\Facades\Response;
use App\Services\Contracts\ISubscriptionService;

class SponsoringController extends Controller
{
    protected ISubscriptionService $subscriptionService;

    protected IUserService $userService;

    public function __construct(ISubscriptionService $subscriptionService, IUserService $userService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->userService = $userService;
    }

    /**
     * Gets the list of all users sponsored by the specified user.
     */
    public function getAllGodchildren(Request $request, $sponsor_id): JsonResponse
    {
        $sponsor = $request->input('sponsor');

        $godchildren = $this->userService->getGodchildren($sponsor);

        return Response::success(['godchildren' => $godchildren]);
    }

    /**
     * Gets the list of users sponsored by the specified user for a specific subscription.
     */
    public function getGodchildren(Request $request, $sponsor_id, $subscription_id): JsonResponse
    {
        $sponsor = $request->input('sponsor');

        $godchildren = $this->userService->getGodchildren($sponsor, $subscription_id);

        return Response::success(['godchildren' => $godchildren]);
    }

    /**
     * Assigns a given subscription to the specified user.
     */
    public function assignSubscription(Request $request, $user_id, $subscription_id): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|numeric|min:1'
        ]);

        $recipient_id = (int) $request->input('recipient_id');

        /**
         * @var User $user
         */
        $user = $this->userService->findOneById($user_id);

        // Verify that user is not trying to assign a subscription to themselves
        if ($user->getKey() === $recipient_id) {
            return Response::error("Self assignment of subscription is forbidden.");
        }

        // Verify the subscription exists
        $subscription = $this->subscriptionService->findOneById($subscription_id);

        $is_assigned = $this->subscriptionService->isAssigned($subscription_id, $recipient_id);

        if ($is_assigned) {
            return Response::error("This subscription has already been assigned to the user.");
        }

        $godchildren_count = count($this->userService->getGodchildren($user, $subscription_id));

        if (($godchildren_count + 1) > $subscription->quantity) {
            return Response::error(Lang::get("No more places available for this subscription."));
        }

        $this->subscriptionService->assignSubscription($subscription_id, $recipient_id);

        return Response::success(Lang::get('The subscription has been assigned to the user.'));
    }

    /**
     * Denies a specified user access to a given subscription.
     */
    public function denySubscription(Request $request, $sponsor_id, $subscription_id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|numeric|min:1'
        ]);

        $user_id = (int) $request->input('user_id');

        // Verify the user exists
        $this->userService->findOneById($user_id);

        // Verify the subscription exists
        $this->subscriptionService->findOneById($subscription_id);

        $is_assigned = $this->subscriptionService->isAssigned($subscription_id, $user_id);

        if (!$is_assigned) {
            return Response::error(Lang::get('This subscription was not assigned to the user.'));
        }

        $this->subscriptionService->denySubscription($subscription_id, $user_id);

        return Response::success(Lang::get('The user is denied access to this subscription.'));
    }
}
