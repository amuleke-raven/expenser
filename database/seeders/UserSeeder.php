<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultProject = Project::where('is_default', true)->firstOrFail();
        $usd = Currency::where('code', 'USD')->firstOrFail();
        $kes = Currency::where('code', 'KES')->firstOrFail();
        $us = Country::where('iso_code', 'US')->firstOrFail();
        $ke = Country::where('iso_code', 'KE')->firstOrFail();

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@remoteraven.com',
                'password' => Hash::make('password'),
                'country_id' => $us->id,
                'currency_id' => $usd->id,
                'role' => 'super_admin',
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@remoteraven.com',
                'password' => Hash::make('password'),
                'country_id' => $us->id,
                'currency_id' => $usd->id,
                'role' => 'manager',
            ],
            [
                'name' => 'Staff',
                'email' => 'staff@remoteraven.com',
                'password' => Hash::make('password'),
                'country_id' => $ke->id,
                'currency_id' => $kes->id,
                'role' => 'staff',
            ],
            [
                'name' => 'Accountant',
                'email' => 'accountant@remoteraven.com',
                'password' => Hash::make('password'),
                'country_id' => $us->id,
                'currency_id' => $usd->id,
                'role' => 'accountant',
            ],
            [
                'name' => 'Backoffice',
                'email' => 'backoffice@remoteraven.com',
                'password' => Hash::make('password'),
                'country_id' => $us->id,
                'currency_id' => $usd->id,
                'role' => 'backoffice',
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['default_project_id' => $defaultProject->id])
            );

            $user->assignRole($role);
            $user->projects()->syncWithoutDetaching([$defaultProject->id]);
        }
    }
}
