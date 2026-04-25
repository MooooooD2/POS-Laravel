<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //  Reset Spatie permission cache on boot
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        //  Add custom Blade directives for permissions
        Blade::if('permission', function ($permission) {
            return auth()->user() && auth()->user()->can($permission);
        });

        Blade::if('role', function ($role) {
            return auth()->user() && auth()->user()->hasRole($role);
        });

        Blade::if('anyrole', function ($roles) {
            if (!auth()->user())
                return false;
            $roles = is_array($roles) ? $roles : func_get_args();
            return auth()->user()->hasAnyRole($roles);
        });

        Blade::if('allroles', function ($roles) {
            if (!auth()->user())
                return false;
            $roles = is_array($roles) ? $roles : func_get_args();
            return auth()->user()->hasAllRoles($roles);
        });
    }
}