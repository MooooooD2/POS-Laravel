<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username'  => fake()->unique()->userName(),
            'password'  => Hash::make('password'),
            'full_name' => fake()->name(),
            'role'      => fake()->randomElement(['admin', 'cashier', 'warehouse']),
            'is_active' => true,
            'language'  => 'ar',
        ];
    }
}
