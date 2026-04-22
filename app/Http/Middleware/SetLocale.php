<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if locale exists in session
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        }
        // Check if user is logged in and has a language preference
        else if (auth()->check() && auth()->user()->language) {
            $locale = auth()->user()->language;
        }
        // Default to Arabic
        else {
            $locale = config('app.locale', 'ar');
        }

        // Validate locale
        if (in_array($locale, ['ar', 'en'])) {
            App::setLocale($locale);
            // Set direction for RTL/LTR
            if ($locale === 'ar') {
                session(['direction' => 'rtl']);
            } else {
                session(['direction' => 'ltr']);
            }
        }

        return $next($request);
    }
}