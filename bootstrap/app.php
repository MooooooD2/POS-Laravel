<?php

use App\Http\Middleware\AnomalyDetection;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SessionSecurity;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // #36 #37 #XSS تطبيق على كل طلبات الويب
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,    // XSS, CSP, Frame protection
            SessionSecurity::class,    // #36 Session hijack detection
            AnomalyDetection::class,   // #37 Anomaly monitoring
        ]);

        $middleware->api(prepend: [
            EncryptCookies::class,
            StartSession::class,
        ]);

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // #48 ردود JSON موحدة — بدون Stack Trace في الإنتاج
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
            }
        });
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية لهذه العملية.'], 403);
            }
        });
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'بيانات غير صالحة.', 'errors' => $e->errors()], 422);
            }
        });
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'العنصر غير موجود.'], 404);
            }
        });
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
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
