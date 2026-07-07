<?php

use App\Exceptions\TicketAlreadyConvertedException;
use App\Exceptions\TicketCannotBeConvertedException;
use App\Helpers\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'public.api_key' => \App\Http\Middleware\VerifyPublicApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport([
            TicketAlreadyConvertedException::class,
            TicketCannotBeConvertedException::class
        ]);

        // error code exception
        $exceptions->render(function (\Throwable $e, Request $request) {

            if (!$request->is('api/*')) {
                return null;
            }

            if ($e instanceof NotFoundHttpException) {
                return ApiResponse::error(
                    'Resource not found.',
                    null,
                    404
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error(
                    'Unauthenticated.',
                    null,
                    401
                );
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error(
                    $e->getMessage() ?: 'Forbidden',
                    null,
                    403
                );
            }

            if ($e instanceof HttpException) {
                return ApiResponse::error(
                    $e->getMessage() ?: 'HTTP Error',
                    null,
                    $e->getStatusCode()
                );
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    'Validation failed.',
                    $e->errors(),
                    422
                );
            }

            return ApiResponse::error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Something went wrong.',
                null,
                500
            );
        });
    })->create();
