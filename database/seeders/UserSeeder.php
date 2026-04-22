<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin - المدير
        User::firstOrCreate(['username' => 'admin'], [
            'password'  => Hash::make('admin123'),
            'full_name' => 'المدير العام',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        // Cashier - الكاشير
        User::firstOrCreate(['username' => 'cashier'], [
            'password'  => Hash::make('cashier123'),
            'full_name' => 'أمين الصندوق',
            'role'      => 'cashier',
            'is_active' => true,
        ]);

        // Warehouse - مسؤول المخزن
        User::firstOrCreate(['username' => 'warehouse'], [
            'password'  => Hash::make('warehouse123'),
            'full_name' => 'مسؤول المخزن',
            'role'      => 'warehouse',
            'is_active' => true,
        ]);
    }
}
