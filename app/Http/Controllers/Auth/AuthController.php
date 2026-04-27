<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Rate limiting: max 5 attempts per minute per IP+username
        $throttleKey = Str::lower($credentials['username']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ], 429);
        }

        if (!Auth::attempt($credentials, false)) {
            RateLimiter::hit($throttleKey, 60);
            return response()->json(['success' => false, 'message' => __('auth.failed')], 401);
        }

        // Check if user is active
        if (!Auth::user()->is_active) {
            Auth::logout();
            return response()->json(['success' => false, 'message' => __('pos.account_disabled')], 403);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return response()->json(['success' => true, 'redirect' => route('dashboard')]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function sessionInfo()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return response()->json([
                'logged_in' => true,
                'username'  => $user->username,
                'full_name' => $user->full_name,
                'role'      => $user->role,
                'language'  => $user->language,
            ]);
        }
        return response()->json(['logged_in' => false]);
    }
}
