<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * FIX-5: 2FA TOTP for admin accounts.
 * Requires: composer require pragmarx/google2fa-laravel bacon/bacon-qr-code
 */
class TwoFactorController extends Controller
{
    public function showVerify()
    {
        if (!Auth::check() || !Auth::user()->google2fa_enabled) {
            return redirect()->route('dashboard');
        }

        return view('auth.2fa.verify');
    }

    public function verify(Request $request)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);

        $user   = Auth::user();
        $google2fa = app('pragmarx.google2fa');

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if (!$valid) {
            return back()->withErrors(['one_time_password' => 'الرمز غير صحيح. حاول مرة أخرى.']);
        }

        $request->session()->put('2fa_passed', true);

        return redirect()->intended(route('dashboard'));
    }

    public function showSetup()
    {
        $user     = Auth::user();
        $google2fa = app('pragmarx.google2fa');

        if ($user->google2fa_enabled) {
            return redirect()->route('dashboard');
        }

        $secret = $google2fa->generateSecretKey();
        session(['2fa_setup_secret' => $secret]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->username,
            $secret
        );

        return view('auth.2fa.setup', compact('secret', 'qrCodeUrl'));
    }

    public function confirmSetup(Request $request)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);

        $user   = Auth::user();
        $secret = session('2fa_setup_secret');
        $google2fa = app('pragmarx.google2fa');

        if (!$secret || !$google2fa->verifyKey($secret, $request->one_time_password)) {
            return back()->withErrors(['one_time_password' => 'الرمز غير صحيح. المسح مرة أخرى.']);
        }

        $recoveryCodes = collect(range(1, 10))->map(fn() => Str::random(10))->toArray();

        $user->update([
            'google2fa_secret'         => encrypt($secret),
            'google2fa_enabled'        => true,
            'google2fa_recovery_codes' => $recoveryCodes,
        ]);

        session()->forget('2fa_setup_secret');
        session(['2fa_passed' => true]);

        return view('auth.2fa.recovery-codes', compact('recoveryCodes'));
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required']);

        if (!password_verify($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة.']);
        }

        Auth::user()->update([
            'google2fa_secret'         => null,
            'google2fa_enabled'        => false,
            'google2fa_recovery_codes' => null,
        ]);

        $request->session()->forget('2fa_passed');

        return redirect()->route('dashboard')->with('success', 'تم تعطيل التحقق بخطوتين.');
    }
}
