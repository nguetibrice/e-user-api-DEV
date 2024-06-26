<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Exceptions\ModelException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\Response as Status;
use App\Services\Contracts\IUserService;
use Illuminate\Support\Facades\Response;
use App\Exceptions\ModelNotFoundException;
use App\Exceptions\NotImplementedException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Fluent;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    protected IUserService $userService;

    public function __construct(IUserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Creates user account
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws ModelException|ModelNotFoundException
     */
    public function createAccount(Request $request): JsonResponse
    {
        $validated_data = $this->validate(
            $request,
            [
                'first_name' => 'required|string|min:4|max:24',
                'last_name' => 'required|string|min:4|max:24',
                'alias' => 'required|string|min:4|max:24',
                'ip' => 'required|ip',
                'phone' => 'sometimes|string',
                'cip' => 'sometimes|string',
                'email' => ['string', 'email', 'max:255', Rule::requiredIf(empty($request->phone))],
                'password' => [
                    'required',
                    'string',
                    // 'min:8',
                    // 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@%-+_!.,@#$^&?%éè]).+$/'
                ],
                'birthday' => 'required|date',
                'code' => 'sometimes|string',
                'guardian' => [
                    'string',
                    Rule::requiredIf($this->checkAge($request->birthday))
                ],
            ]
        );
        $verif_user = User::where("alias", $request->alias)->first();
        if ($verif_user) {
            if (($verif_user->email == $request->email || $verif_user->phone == $request->phone)
                && $verif_user->email_verified_at == null && $request->cip != null) {
                // trigger event to send asynchronous email or sms notification to the user
                event(new Registered($verif_user));
                $data = $this->userService->generateUserTokenFromIp($verif_user, $request->input('ip'));

                $data['message'] = Lang::get(
                    "Utilisateur créé avec succès. Veuillez vérifier votre courrier électronique ou votre téléphone pour
                    obtenir un code PIN à 6 chiffres afin de vérifier votre compte."
                );

                return Response::success($data, Status::HTTP_CREATED);
            } else {
                return Response::error("L'alias a deja ete pris", Status::HTTP_BAD_REQUEST);
            }
        }

        if ($request->code != null) {
            $referrer = User::where('alias', $request->code)->orWhere('cip', $request->code)->first();
            if ($referrer == null) {
                return Response::error("Refferant Inconnu", Status::HTTP_BAD_REQUEST);
            }
        }
        if ($request->guardian != null) {
            if ($this->userService->getUserByAlias($request->guardian) != null) {
                $guardian = $this->userService->getUserByAlias($request->guardian);
            } elseif ($this->userService->getUserByEmail($request->guardian) != null) {
                $guardian = $this->userService->getUserByEmail($request->guardian);
            } elseif ($this->userService->getUserByCip($request->guardian) != null) {
                $guardian = $this->userService->getUserByCip($request->guardian);
            } elseif ($this->userService->getUserByPhone($request->guardian) != null) {
                $guardian = $this->userService->getUserByPhone($request->guardian);
            } else {
                return Response::error("Tuteur Inconnu, bien vouloir verifier que le compte du tuteur a bien ete cree", Status::HTTP_BAD_REQUEST);
            }
        }
        if ($request->cip != null) {
            $user = $this->userService->getUserByCip($request->cip);
            $user = $this->userService->updateAccount($user, $validated_data);
        } else {

            $user = new User([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'alias' => $request->input('alias'),
                'phone' => str_replace(" ", "", $request->input('phone')),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'cip' => bin2hex(random_bytes(3)),
                'birthday' => $request->input('birthday'),
                'referrer' => $request->input('code') == null? null : $referrer->id,
                'guardian' => $request->input('guardian') == null? null : $guardian->id,
            ]);
            $this->userService->createAccount($user);
        }

        $data = $this->userService->generateUserTokenFromIp($user, $request->input('ip'));

        $data['message'] = Lang::get(
            "Utilisateur créé avec succès. Veuillez vérifier votre courrier électronique ou votre téléphone pour
            obtenir un code PIN à 6 chiffres afin de vérifier votre compte."
        );

        return Response::success($data, Status::HTTP_CREATED);
    }


    /**
     * Activates user account
     *
     * @throws ValidationException
     * @throws ModelNotFoundException|ModelException
     */
    public function activateAccount(Request $request): JsonResponse
    {
        $this->validate($request, ['pin' => 'required|int|min:100000|max:999999']);
        $pin = intval($request->input('pin'));
        $user = $this->userService->getUserFromGuard();

        if (!$user->hasVerifiedAccount()) {
            return Response::error(Lang::get('Your Account is already active'), Status::HTTP_UNAUTHORIZED);
        }

        if ($this->userService->getLastActivationCode($user) !== $pin) {
            return Response::error(Lang::get('Invalid activation code'), Status::HTTP_UNAUTHORIZED);
        }

        $this->userService->markAccountAsVerified($user);
        verifyCustomer($user);

        return Response::success(['message' => 'Account successfully verified.']);
    }


    /**
     * @throws NotImplementedException
     */
    public function resendActivationCode(): JsonResponse
    {
        $user = $this->userService->getUserFromGuard();

        $data = $this->userService->resentActivationCode($user);

        $data['message'] = Lang::get(
            "Please check your email or phone a new pin has just been sent."
        );
        return Response::success($data, Status::HTTP_CREATED);
    }

    private function checkAge($birthday)
    {
        if ($birthday) {
            $date1=date_create(date("Y-m-d"));
            $date2=date_create($birthday);
            $diff=date_diff($date1,$date2);
            return $diff->y >= 18;
        }
        return false;
    }
}
