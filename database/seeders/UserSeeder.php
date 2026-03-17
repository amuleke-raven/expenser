<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => UserRole::Admin],
            ['name' => 'Finance User', 'email' => 'finance@example.com', 'role' => UserRole::Finance],
            ['name' => 'Manager User', 'email' => 'manager@example.com', 'role' => UserRole::Manager],
            ['name' => 'Staff User', 'email' => 'staff@example.com', 'role' => UserRole::Staff],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'role' => $userData['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
