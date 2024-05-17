<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\UserService;
use PhpParser\Node\Stmt\Return_;
use Illuminate\Http\JsonResponse;
use App\Exceptions\ModelException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Response as Status;
use App\Services\Contracts\IUserService;
use Illuminate\Support\Facades\Response;
use App\Exceptions\ModelNotFoundException;
use App\Exceptions\NotImplementedException;
use Illuminate\Http\Response as HTTPResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected IUserService $userService;
    /**
     * AuthController constructor.
     * @param IUserService $userService
     */
    public function __construct(IUserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Creates a new session for the user based on his alias, password and ip
     *
     * @throws ModelNotFoundException
     */
    public function openSession(Request $request): JsonResponse
    {
        // return Response::error($request->input('password'), HTTPResponse::HTTP_FORBIDDEN);

        $request->validate(['alias' => 'required|string', 'password' => 'required|string', 'ip' => 'required|ip']);

        $alias = $request->input('alias');

        if (!$this->userService->hasValidCredentials($alias, $request->input('password'))) {
            $reason = Lang::get("'alias' & 'password' do not match with our records");

            return Response::error($reason, HTTPResponse::HTTP_FORBIDDEN);
        }

        $user = $this->userService->getUserByAlias($alias);
        if ($user->email_verified_at == null) {
            event(new Registered($user));
                $data = $this->userService->generateUserTokenFromIp($user, $request->input('ip'));

                $data['message'] = Lang::get(
                    "Utilisateur créé avec succès. Veuillez vérifier votre courrier électronique ou votre téléphone pour
                    obtenir un code PIN à 6 chiffres afin de vérifier votre compte."
                );
                $data["type"] = "redirect";
                // password session
                return Response::success($data, Status::HTTP_CREATED);
        }
        // $customer = verifyCustomer($user);

        return Response::success([
            "token" => $this->userService->generateUserTokenFromIp($user, $request->input('ip')),
            // 'wallet_balance'=> strtoupper($customer["default_currency"])." " . $customer["balance"]
        ]);
    }


    public function userIsConnect(User $user): JsonResponse
    {
        $user = $this->userService->getUserFromGuard();

        return Response::success($user->currentAccessToken());
    }

    /**
     * usernvaluserdates user session
     *
     * @return JsonResponse
     * @throws ModelNotFoundException|ModelException
     */
    public function closeSession(): JsonResponse
    {
        $user = $this->userService->getUserFromGuard();
        $this->userService->destroySession($user);

        return Response::success(['message' => Lang::get('Session successfully closed.')]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showAuthUSer()
    {
        $user = $this->userService->getUserFromGuard();
        return Response::success(['user' => $user]);
    }

    public function getUser($id)
    {
        $user = $this->userService->findOneById($id);

        return Response::success(['user' => $user]);
    }
}
