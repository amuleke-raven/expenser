<?php

namespace Database\Seeders;

use App\Models\SlaPolicy;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TicketConfigSeeder extends Seeder
{
    public function run(): void
    {
        // SLA policies
        $slaPolicies = [
            ['priority' => 'critical', 'response_hours' => 1, 'resolve_hours' => 4],
            ['priority' => 'high', 'response_hours' => 4, 'resolve_hours' => 8],
            ['priority' => 'medium', 'response_hours' => 8, 'resolve_hours' => 24],
            ['priority' => 'low', 'response_hours' => 24, 'resolve_hours' => 72],
        ];

        foreach ($slaPolicies as $policy) {
            SlaPolicy::firstOrCreate(['priority' => $policy['priority']], $policy);
        }

        // IT staff role and permissions
        $itStaffPermission = Permission::firstOrCreate(['name' => 'access_it_panel']);
        Permission::firstOrCreate(['name' => 'access_admin_panel']);
        $itStaffRole = Role::firstOrCreate(['name' => 'it_staff']);
        $itStaffRole->givePermissionTo($itStaffPermission);

        // IT Support user
        $itUser = User::firstOrCreate(
            ['email' => 'it@remoteraven.com'],
            [
                'name' => 'IT Support',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $itUser->assignRole('it_staff');

        // Ticket categories
        $categories = [
            [
                'name' => 'Hardware Issue',
                'slug' => 'hardware-issue',
                'icon' => 'computer-desktop',
                'sla_hours' => 8,
                'default_assignee_id' => $itUser->id,
            ],
            [
                'name' => 'Software / App',
                'slug' => 'software-app',
                'icon' => 'code-bracket',
                'sla_hours' => 24,
                'default_assignee_id' => $itUser->id,
            ],
            [
                'name' => 'VPN & Remote Access',
                'slug' => 'vpn-remote-access',
                'icon' => 'globe-alt',
                'sla_hours' => 4,
                'default_assignee_id' => $itUser->id,
            ],
            [
                'name' => 'Account & Permissions',
                'slug' => 'account-permissions',
                'icon' => 'key',
                'sla_hours' => 8,
                'default_assignee_id' => $itUser->id,
            ],
            [
                'name' => 'Workstation Setup',
                'slug' => 'workstation-setup',
                'icon' => 'wrench-screwdriver',
                'sla_hours' => 48,
                'default_assignee_id' => $itUser->id,
            ],
            [
                'name' => 'Other / General',
                'slug' => 'other-general',
                'icon' => 'question-mark-circle',
                'sla_hours' => 24,
                'default_assignee_id' => null,
            ],
        ];

        foreach ($categories as $category) {
            TicketCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
