<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

abstract class BaseException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        $result = null;
        if ($request) {
            $result = Response::error($this->getMessage(), $this->getCode());
        }

        return $result;
    }
}
