<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // FIX-08: حُذف forgetCachedPermissions() — كان يُبطل Cache في كل طلب HTTP
        // Spatie يدير cache الصلاحيات تلقائياً بشكل صحيح

        // Custom Blade directives for permissions
        Blade::if('permission', function ($permission) {
            return auth()->user() && auth()->user()->can($permission);
        });

        Blade::if('role', function ($role) {
            return auth()->user() && auth()->user()->hasRole($role);
        });

        Blade::if('anyrole', function ($roles) {
            if (!auth()->user()) return false;
            $roles = is_array($roles) ? $roles : func_get_args();
            return auth()->user()->hasAnyRole($roles);
        });

        Blade::if('allroles', function ($roles) {
            if (!auth()->user()) return false;
            $roles = is_array($roles) ? $roles : func_get_args();
            return auth()->user()->hasAllRoles($roles);
        });
    }
}
