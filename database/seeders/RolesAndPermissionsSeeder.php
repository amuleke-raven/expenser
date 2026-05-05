<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage_users',
            'manage_expenses',
            'approve_expenses',
            'manage_rewards',
            'approve_rewards',
            'manage_workflows',
            'manage_payments',
            'view_reports',
            'access_admin_panel',
            'access_staff_panel',
            'create-backoffice-expenses',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = ['super_admin', 'admin', 'manager', 'staff', 'accountant'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        Role::findByName('super_admin')->givePermissionTo(Permission::all());

        foreach (['admin', 'accountant'] as $role) {
            Role::findByName($role)->givePermissionTo('access_admin_panel');
        }

        foreach (['manager', 'staff'] as $role) {
            Role::findByName($role)->givePermissionTo('access_staff_panel');
        }

        Role::findByName('admin')->givePermissionTo('approve_rewards');
    }
}
