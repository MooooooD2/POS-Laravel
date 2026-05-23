<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * SECURITY FIX: previously extended AuthServiceProvider, which caused every policy
 * to be registered twice — once here and once in AuthServiceProvider — leading to
 * unpredictable policy resolution depending on provider boot order.
 *
 * All policy registrations live exclusively in AuthServiceProvider.  This class now
 * extends the plain ServiceProvider and handles only application bootstrap concerns
 * (rate limiting, Blade directives).
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Outputs nonce="..." for inline <script> tags to satisfy CSP
        Blade::directive('nonce', function () {
            return "<?php echo 'nonce=\"' . (app()->has('csp-nonce') ? app('csp-nonce') : '') . '\"'; ?>";
        });

        // Custom Blade directives for permissions
        // Cast to App\Models\User so the IDE resolves Spatie HasRoles methods correctly.
        Blade::if('permission', function ($permission) {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            return $user && $user->can($permission);
        });

        Blade::if('role', function ($role) {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            return $user && $user->hasRole($role);
        });

        Blade::if('anyrole', function ($roles) {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            if (!$user) return false;
            $roles = \is_array($roles) ? $roles : \func_get_args();
            return $user->hasAnyRole($roles);
        });

        Blade::if('allroles', function ($roles) {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            if (!$user) return false;
            $roles = \is_array($roles) ? $roles : \func_get_args();
            return $user->hasAllRoles($roles);
        });
    }
}
