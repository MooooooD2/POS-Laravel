<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceTwoFactor
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        // Check if user is logged in and needs 2FA
        if ($user && $user->two_factor_enabled && !$request->session()->has('2fa_verified')) {

            // CRITICAL: Do not redirect if already on the 2FA verification page
            if (!$request->is('auth/2fa*')) {
                return redirect()->route('auth.2fa.verify');
            }
        }

        return $next($request);
    }
}