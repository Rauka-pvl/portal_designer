<?php

use App\Http\Middleware\EnsureDesignerSubscription;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureSupplierDepositPaid;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'password.changed' => EnsurePasswordIsChanged::class,
            'subscription.active' => EnsureDesignerSubscription::class,
            'deposit.paid' => EnsureSupplierDepositPaid::class,
        ]);
        $middleware->web(append: [SetLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\PlanLimitExceeded $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json($exception->payload() + ['code' => $exception->errorCode], 422);
            }

            return back()
                ->withErrors(['limit' => $exception->getMessage()])
                ->with('limit_exceeded', $exception->payload());
        });

        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($request->is('api/*') && $exception->getStatusCode() === 403) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'Forbidden',
                    'code' => 'forbidden',
                ], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'Not found',
                    'code' => 'not_found',
                ], 404);
            }
        });
    })->create();
