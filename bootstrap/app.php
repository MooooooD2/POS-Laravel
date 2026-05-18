<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        App\Providers\TenancyServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\InitializeTenancyBySession::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SessionSecurity::class,
            \App\Http\Middleware\AnomalyDetection::class,
        ]);

        // API routes share session-based auth — prepend cookie+session+tenancy
        // so they run BEFORE the default throttle:api and SubstituteBindings.
        $middleware->api(
            prepend: [
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \App\Http\Middleware\InitializeTenancyBySession::class,
            ]
        );

        $middleware->alias([
            'tenancy'            => \App\Http\Middleware\InitializeTenancyBySession::class,
            '2fa'                => \App\Http\Middleware\EnforceTwoFactor::class,
            'ip.whitelist'       => \App\Http\Middleware\IpWhitelist::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\InitializeTenancyBySession::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \App\Http\Middleware\EnforceTwoFactor::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // #48 ردود JSON موحدة — بدون Stack Trace في الإنتاج
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $_, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
            }
        });
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $_, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية لهذه العملية.'], 403);
            }
        });
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'بيانات غير صالحة.', 'errors' => $e->errors()], 422);
            }
        });
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $_, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'العنصر غير موجود.'], 404);
            }
        });
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $_, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'طلبات كثيرة جداً. حاول بعد دقيقة.'], 429);
            }
        });

        // Catch-all: any unhandled exception on an AJAX/API request returns JSON
        // instead of an HTML error page. Never expose the real message in production.
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $status  = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $message = app()->hasDebugModeEnabled()
                    ? $e->getMessage()
                    : (__('pos.server_error', [], 'en') ?: 'An unexpected error occurred.');
                return response()->json(['success' => false, 'message' => $message], $status);
            }
        });
    })->create();
