<?php namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:200',
        ]);

        if (Auth::attempt([
            'username'  => $credentials['username'],
            'password'  => $credentials['password'],
            'is_active' => true,
        ])) {
            $request->session()->regenerate();

            Log::channel('audit')->info('auth.login_success', [
                'user_id'    => Auth::id(),
                'username'   => $credentials['username'],
                'ip'         => $request->ip(),
                'user_agent' => $this->sanitizeUserAgent($request->userAgent()),
                'timestamp'  => now()->toIso8601String(),
            ]);

            return response()->json(['success' => true, 'redirect' => route('dashboard')]);
        }

        // Only reveal "account disabled" when the password is also correct.
        // This prevents username enumeration via disabled accounts.
        $user = User::where('username', $credentials['username'])->first();
        if ($user && !$user->is_active && Hash::check($credentials['password'], $user->password)) {
            Log::channel('audit')->warning('auth.login_blocked_inactive', [
                'username'  => $credentials['username'],
                'ip'        => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('auth.account_disabled'),
            ], 403);
        }

        Log::channel('audit')->warning('auth.login_failed', [
            'username'  => $credentials['username'],
            'ip'        => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => false,
            'message' => __('auth.failed'),
        ], 401);
    }

    public function logout(Request $request)
    {
        Log::channel('audit')->info('auth.logout', [
            'user_id'   => Auth::id(),
            'username'  => Auth::user()?->username,
            'ip'        => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

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
            ]);
        }
        return response()->json(['logged_in' => false]);
    }

    private function sanitizeUserAgent(?string $ua): string
    {
        if (!$ua) return 'unknown';
        return substr(preg_replace('/[\x00-\x1F\x7F]/', '', $ua), 0, 250);
    }
}
