<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['BAC Test', 'bac@test.com', Role::Bac],
            ['Procurement Test', 'procurement@test.com', Role::Procurement],
            ['Admin Test', 'admin@test.com', Role::Admin],
        ] as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password'), 'role' => $role]
            );
        }
    }
}