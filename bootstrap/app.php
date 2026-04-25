<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global web middleware
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // ✅ FIX: Register Spatie role/permission middleware aliases
        $middleware->alias([
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'has.role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle unauthorized role access gracefully
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => __('pos.unauthorized')], 403);
            }
            abort(403, __('pos.unauthorized'));
        });
    })->create();
