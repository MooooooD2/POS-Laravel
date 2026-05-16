<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class InitializeTenancyBySession
{
    public function handle(Request $request, Closure $next)
    {
        if (tenancy()->initialized) {
            return $next($request);
        }

        $tenantId = $request->session()->get('tenant_id');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);

            if ($tenant && $tenant->is_active) {
                tenancy()->initialize($tenant);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            } elseif ($tenant && !$tenant->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')
                    ->withErrors(['tenant' => __('pos.tenant_deactivated')]);
            } else {
                // Tenant deleted — clear stale session
                $request->session()->forget('tenant_id');
            }
        }

        return $next($request);
    }
}
