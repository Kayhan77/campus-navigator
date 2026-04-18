<?php

namespace App\Exceptions;

use Throwable;
use App\Helpers\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        // Keep all API rendering logic centralized in render().
    }


    public function render($request, Throwable $e)
    {
        if (! $request->expectsJson()) {
            return parent::render($request, $e);
        }

        if ($e instanceof ApiException) {
            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrors()
            );
        }

        if ($e instanceof ValidationException) {
            return ApiResponse::error(
                'Validation failed',
                422,
                $e->errors()
            );
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error('Unauthenticated', 401);
        }

        if ($e instanceof AuthorizationException) {
            return ApiResponse::error('Forbidden', 403);
        }

        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::error('Resource not found', 404);
        }

        if (config('app.debug')) {
            return ApiResponse::error(
                $e->getMessage(),
                500,
                ['exception' => $e::class]
            );
        }

        return ApiResponse::error('Server Error', 500);
    }
}
