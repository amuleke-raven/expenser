<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Department;
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

        $engineering = Department::where('name', 'Engineering')->firstOrFail();
        $finance = Department::where('name', 'Finance')->firstOrFail();

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@remoteraven.com',
                'password' => Hash::make('password'),
                'country_id' => $us->id,
                'currency_id' => $usd->id,
                'role' => 'super_admin',
                'department_id' => null,
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@remoteraven.com',
                'password' => Hash::make('password'),
                'country_id' => $us->id,
                'currency_id' => $usd->id,
                'role' => 'manager',
                'department_id' => $engineering->id,
            ],
            [
                'name' => 'Staff',
                'email' => 'staff@remoteraven.com',
                'password' => Hash::make('password'),
                'country_id' => $ke->id,
                'currency_id' => $kes->id,
                'role' => 'staff',
                'department_id' => $engineering->id,
            ],
            [
                'name' => 'Accountant',
                'email' => 'accountant@remoteraven.com',
                'password' => Hash::make('password'),
                'country_id' => $us->id,
                'currency_id' => $usd->id,
                'role' => 'accountant',
                'department_id' => $finance->id,
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['default_project_id' => $defaultProject->id])
            );

            $user->update(['department_id' => $userData['department_id']]);

            $user->assignRole($role);
            $user->projects()->syncWithoutDetaching([$defaultProject->id]);
        }
    }
}
