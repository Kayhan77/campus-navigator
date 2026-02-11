<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use App\Helpers\ApiResponse;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        //
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {

            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    'Validation failed',
                    422,
                    $e->errors()
                );
            }

            return ApiResponse::error(
                $e->getMessage(),
                500
            );
        }

        return parent::render($request, $e);
    }
}
