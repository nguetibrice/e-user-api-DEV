<?php

namespace App\Http\Controllers;

use App\Services\Contracts\IUserLevelsService;
use App\Services\Contracts\IUserService;
use Illuminate\Http\Request;
use Response;

class UserLevelsController extends Controller
{
    //
    protected IUserLevelsService $userLevelsService;
    protected IUserService $userService;
    public function __construct(
        IUserLevelsService $userLevelsService,
        IUserService $userService
    ) {
        $this->userLevelsService = $userLevelsService;
        $this->userService = $userService;
    }
    public function index()
    {
        return Response::success([
            "user_levels" => $this->userLevelsService->getUserLevels(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            "alias" => "required|exists:users,alias",
            "product_name" => "required",
            "level" => "required|numeric",
        ]);

        // check product here

        //
        $data = $request->all();
        $data["product_name"] = strtoupper($data["product_name"]);
        $data["user_id"] = $this->userService->getUserByAlias($data["alias"])->id;

        $level = $this->userLevelsService->addUserLevel($data);

        return Response::success([
            "user_level" => $level,
        ]);
    }
}
