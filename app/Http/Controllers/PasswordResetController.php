<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use App\Services\Contracts\IUserService;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Response;

class PasswordResetController extends Controller
{
    protected IUserService $userService;

    public function __construct(IUserService $userService)
    {
        $this->userService = $userService;
    }

    public function sendResetCodeEmail(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'reset-password-url' => 'required|url'
        ]);

        $status = $this->userService->requestPasswordReset($data['email'], $data['reset-password-url']);

        switch ($status) {
            case Password::RESET_LINK_SENT:
                $response = Response::success(['message' => Lang::get('Notification sent')]);
                break;

            case Password::INVALID_USER:
                $response = Response::error(Lang::get('The user is not found'));
                break;

            case Password::RESET_THROTTLED:
                $response = Response::error(Lang::get('The reset attempt was throttled'));
                break;
        }

        return $response;
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@%-+_!.,@#$^&?%éè]).+$/',
                'confirmed'
            ],
        ]);

        $status = $this->userService->resetPassword(
            $request->only('email', 'password', 'password_confirmation', 'token')
        );

        switch ($status) {
            case Password::PASSWORD_RESET:
                $response = Response::success(['message' => Lang::get('Successful password reset')]);
                break;

            case Password::INVALID_USER:
                $response = Response::error(Lang::get('The user is not found'));
                break;

            case Password::INVALID_TOKEN:
                $response = Response::error(Lang::get('The token is not valid'));
                break;

            case Password::RESET_THROTTLED:
                $response = Response::error(Lang::get('The reset attempt was throttled'));
                break;
        }

        return $response;
    }
}
