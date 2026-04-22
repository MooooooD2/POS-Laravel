<?php
// =============================================================
// MIDDLEWARE & CONFIG FILES - ملفات الوسيط والإعداد
// =============================================================

// ---------------------------------------------------------------
// FILE: app/Http/Middleware/SetLocale.php
// ---------------------------------------------------------------
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', config('app.locale', 'ar'));

        if (in_array($locale, ['ar', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}

// ---------------------------------------------------------------
// FILE: bootstrap/app.php  (Add middleware)
// ---------------------------------------------------------------
/*
Add to withMiddleware():

$middleware->web(append: [
    \App\Http\Middleware\SetLocale::class,
]);
*/

// ---------------------------------------------------------------
// FILE: config/app.php  (Change locale)
// ---------------------------------------------------------------
/*
'locale' => 'ar',
'fallback_locale' => 'en',
'faker_locale' => 'ar_SA',
*/

// ---------------------------------------------------------------
// FILE: app/Http/Middleware/Authenticate.php  (Redirect to login)
// ---------------------------------------------------------------
namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}

// ---------------------------------------------------------------
// FILE: config/auth.php  (Set username guard)
// ---------------------------------------------------------------
/*
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
    ],
],
*/

// ---------------------------------------------------------------
// IMPORTANT: Update User model to use username instead of email
// In app/Models/User.php, the login uses 'username' field.
// In AuthController, Auth::attempt uses ['username' => ...].
// Make sure there's NO 'email' required validation in the default
// Laravel auth setup.
// ---------------------------------------------------------------

// ---------------------------------------------------------------
// FILE: .env.example  (Environment template)
// ---------------------------------------------------------------
/*
APP_NAME="نظام نقطة البيع"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_system
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=480

QUEUE_CONNECTION=sync
*/
