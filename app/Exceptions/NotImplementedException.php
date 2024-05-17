<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class NotImplementedException extends BaseException
{
    public function __construct(string $message)
    {
        $code = Response::HTTP_NOT_IMPLEMENTED;

        parent::__construct($message, $code);
    }
}
