<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use App\Services\Contracts\IUserService;
use Illuminate\Support\Facades\Response;
use App\Services\Contracts\ISubscriptionService;

class VerifySponsor
{
    protected IUserService $userService;
    protected ISubscriptionService $subscriptionService;

    public function __construct(ISubscriptionService $subscriptionService, IUserService $userService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->userService = $userService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $sponsor_id = $request->route('sponsor_id');

        if (!is_numeric($sponsor_id)) {
            throw new \RuntimeException(Lang::get("The sponsor's identifier must be an integer"));
        }

        /**
         * @var User $user
         */
        $user = $this->userService->findOneById((int) $sponsor_id);

        // Verify the user is a sponsor
        if (empty($user->subscriptionAssignments->all())) {
            return Response::error(Lang::get("This user is not sponsor !"));
        }

        $request->merge(['sponsor' => $user]);

        return $next($request);
    }
}
