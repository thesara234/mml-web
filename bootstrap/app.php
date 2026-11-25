<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Send guests to the right login screen for the area they tried to open.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('portal', 'portal/*')
            ? route('portal.login')
            : route('staff.login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->is('portal', 'portal/*')
            ? route('portal.dashboard')
            : route('staff.dashboard'));

        $middleware->alias([
            'staff.manager' => \App\Http\Middleware\EnsureStaffCanManage::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Mobile API always gets JSON, never an HTML redirect.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Your session has expired. Please log in again.'], 401);
            }
        });
    })->create();
