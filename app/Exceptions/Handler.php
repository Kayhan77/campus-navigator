<?php

namespace App\Exceptions;

use Throwable;
use App\Helpers\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $this->reportable(function (Throwable $e): void {
            if (! request()?->expectsJson()) {
                return;
            }

            logger()->error('Unhandled exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'user_id' => request()->user()?->id,
                'path' => request()->path(),
                'method' => request()->method(),
                'request' => request()->except([
                    'password',
                    'password_confirmation',
                    'current_password',
                    'token',
                    'access_token',
                    'refresh_token',
                ]),
            ]);
        });
    }


    public function render($request, Throwable $e)
    {
        if (! $request->expectsJson()) {
            return parent::render($request, $e);
        }

        // Custom business exceptions (already formatted)
        if ($e instanceof ApiException) {
            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrors()
            );
        }

        // Validation errors (422)
        if ($e instanceof ValidationException) {
            return ApiResponse::error(
                'Validation failed',
                422,
                $e->errors()
            );
        }

        // Authentication errors (401)
        if ($e instanceof AuthenticationException) {
            return ApiResponse::error('Unauthenticated', 401);
        }

        // Authorization errors (403)
        if ($e instanceof AuthorizationException) {
            return ApiResponse::error('Forbidden', 403);
        }

        // Rate limiting (429)
        if ($e instanceof ThrottleRequestsException) {
            return ApiResponse::error(
                'Too many requests. Please try again later.',
                429
            );
        }

        // Model not found (404)
        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::error('Resource not found', 404);
        }

        // Route not found (404)
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::error('Resource not found', 404);
        }

        // Database errors (500) - hide details in production
        if ($e instanceof QueryException) {
            logger()->error('Database query failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'An error occurred while processing your request.';

            return ApiResponse::error($message, 500);
        }

        // HTTP exceptions with custom status codes
        if ($e instanceof HttpException) {
            return ApiResponse::error(
                $e->getMessage() ?: 'An error occurred',
                $e->getStatusCode()
            );
        }

        // Catch-all for unexpected errors (500)
        logger()->error('Unhandled exception', [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'user_id' => $request->user()?->id,
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        if (config('app.debug')) {
            return ApiResponse::error(
                $e->getMessage(),
                500,
                ['exception' => $e::class]
            );
        }

        return ApiResponse::error('An unexpected error occurred. Please try again later.', 500);
    }
}
