<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use App\Services\Contracts\IUserService;
use Illuminate\Support\Facades\Response;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(IUserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function packageFind(): JsonResponse
    {
        $user = $this->userService->getUserFromGuard();
        $userPackage = $this->userService->findPackage($user);

        return Response::success([
            "subscription" => $userPackage,
            "user" => $user
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        //
    }

    /**
     * Find a user by its alias.
     *
     * @return \Illuminate\Http\Response
     */
    public function find(Request $request)
    {
        $request->validate([
            'alias' => 'required|string|min:4'
        ]);

        $alias = $request->input('alias');
        $user = $this->userService->getUserByAlias($alias);

        return Response::success(['user' => $user]);
    }

    public function getUserFromCip($cip)
    {
        $user = $this->userService->getUserByCip($cip);
        return Response::success(['user' => $user]);
    }
    /**
     * Update the password of the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@%-+_!.,@#$^&?%éè]).+$/'
            ],
        ]);

        $password_update =  [
            'password' => Hash::make($request->input('password')),
        ];
        $user = $this->userService->getUserFromGuard();
        $this->userService->updateUser($user, $password_update);
        return Response::success(['message' => Lang::get('User password updated')]);
    }

    /**
     * Update the profile data of the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|min:4|max:24',
            'last_name' => 'required|string|min:4|max:24',
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'phone' => 'sometimes|string',
        ]);

        $user =  $this->userService->getUserFromGuard();
        $this->userService->updateUser($user, $data);
        return Response::success(['message' => Lang::get('User profile updated')]);
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        $user = $this->userService->getUserFromGuard();
        $this->userService->destroyUserAccount($user);
        return Response::success(['message' => Lang::get('User account deleted')]);
    }
}
