<?php

use App\Domain\Shared\Enums\ApiErrorCode;
use App\Exceptions\DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $jsonError = function (string $message, ApiErrorCode $code, int $status, array $details = []) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $code->value,
                    'message' => $message,
                    'details' => empty($details) ? new stdClass : $details,
                ],
            ], $status);
        };

        $exceptions->render(function (ValidationException $e, Request $request) use ($jsonError) {
            if ($request->expectsJson()) {
                return $jsonError('Validation failed.', ApiErrorCode::ValidationFailed, 422, $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($jsonError) {
            if ($request->expectsJson()) {
                return $jsonError('Unauthenticated.', ApiErrorCode::Unauthorized, 401);
            }
        });

        // ModelNotFoundException is converted to NotFoundHttpException by Laravel's prepareException()
        // before reaching renderers, so we match on NotFoundHttpException here.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($jsonError) {
            if ($request->expectsJson()) {
                return $jsonError('Resource not found.', ApiErrorCode::ResourceNotFound, 404);
            }
        });

        $exceptions->render(function (DomainException $e, Request $request) use ($jsonError) {
            if ($request->expectsJson()) {
                return $jsonError($e->getMessage(), $e->errorCode, $e->statusCode, $e->details);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($jsonError) {
            if ($request->expectsJson()) {
                $details = config('app.debug')
                    ? ['exception' => $e->getMessage(), 'trace' => $e->getTrace()]
                    : [];

                return $jsonError('An unexpected error occurred.', ApiErrorCode::InternalError, 500, $details);
            }
        });
    })->create();
