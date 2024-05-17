<?php

namespace App\Services;

use App\Models\User;
use App\Exceptions\ModelException;
use App\Services\Contracts\IUserLevelsService;
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
use App\Models\UserLevel;
use Cache;

class UserLevelsService extends BaseService implements IUserLevelsService
{
    public function getUserLevels()
    {
        return Cache::remember('user_levels', now()->addHour(), function () {
            $user_levels = [];

            foreach (UserLevel::with("user")->get() as $user_level) {
                $user_levels[] = $user_level;
            };

            return $user_levels;
        });
    }

    public function addUserLevel(array $data): UserLevel
    {
        $user_level = new UserLevel($data);

        $this->insert($user_level);
        Cache::forget('user_levels');

        return $user_level;
    }

    /**
     * @inheritDoc
     */
    protected function getModelObject(): User
    {
        return new User();
    }
}
