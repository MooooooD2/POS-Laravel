<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function start(Request $request, User $user)
    {
        $admin = Auth::user();

        if ($admin->id === $user->id) {
            return back()->withErrors(['impersonate' => __('pos.impersonate_self_error')]);
        }

        $request->session()->put('impersonator_id', $admin->id);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function leave(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');

        if (!$adminId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($adminId);

        if (!$admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login');
        }

        Auth::login($admin);

        return redirect()->route('dashboard');
    }
}
