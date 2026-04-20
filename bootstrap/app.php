<?php

use App\Exceptions\ApiException;
use App\Helpers\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'admin'    => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e): void {
            $request = request();

            logger()->error('Unhandled exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'user_id' => $request?->user()?->id,
                'path' => $request?->path(),
                'method' => $request?->method(),
                'request' => $request?->except([
                    'password',
                    'password_confirmation',
                    'current_password',
                    'token',
                    'access_token',
                    'refresh_token',
                ]) ?? [],
            ]);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof ApiException) {
                return ApiResponse::error(
                    $e->getMessage(),
                    $e->getStatusCode(),
                    $e->getErrors()
                );
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error('Validation failed', 422, $e->errors());
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiResponse::error('Resource not found', 404);
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated', 401);
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error('Forbidden', 403);
            }

            if (config('app.debug')) {
                return ApiResponse::error(
                    $e->getMessage(),
                    500,
                    ['exception' => $e::class]
                );
            }

            return ApiResponse::error('Server Error', 500);
        });
    })->create();
