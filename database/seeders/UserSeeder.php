<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ FIX: Create roles first
        $adminRole     = Role::firstOrCreate(['name' => 'admin',     'guard_name' => 'web']);
        $cashierRole   = Role::firstOrCreate(['name' => 'cashier',   'guard_name' => 'web']);
        $warehouseRole = Role::firstOrCreate(['name' => 'warehouse',  'guard_name' => 'web']);

        // ✅ FIX: Use env() for passwords — never hardcode in production
        $admin = User::firstOrCreate(['username' => 'admin'], [
            'password'  => Hash::make(env('ADMIN_PASSWORD', 'Admin@2026!')),
            'full_name' => 'المدير العام',
            'role'      => 'admin',
            'is_active' => true,
            'language'  => 'ar',
        ]);
        $admin->syncRoles([$adminRole]);

        $cashier = User::firstOrCreate(['username' => 'cashier'], [
            'password'  => Hash::make(env('CASHIER_PASSWORD', 'Cashier@2026!')),
            'full_name' => 'أمين الصندوق',
            'role'      => 'cashier',
            'is_active' => true,
            'language'  => 'ar',
        ]);
        $cashier->syncRoles([$cashierRole]);

        $warehouse = User::firstOrCreate(['username' => 'warehouse'], [
            'password'  => Hash::make(env('WAREHOUSE_PASSWORD', 'Warehouse@2026!')),
            'full_name' => 'مسؤول المخزن',
            'role'      => 'warehouse',
            'is_active' => true,
            'language'  => 'ar',
        ]);
        $warehouse->syncRoles([$warehouseRole]);
    }
}
