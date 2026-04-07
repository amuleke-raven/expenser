<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TicketSystemSeeder extends Seeder
{
    public function run(): void
    {
        // Seed SLA policies
        $slaPolicies = [
            ['priority' => 'critical', 'response_hours' => 1, 'resolve_hours' => 4],
            ['priority' => 'high', 'response_hours' => 4, 'resolve_hours' => 8],
            ['priority' => 'medium', 'response_hours' => 8, 'resolve_hours' => 24],
            ['priority' => 'low', 'response_hours' => 24, 'resolve_hours' => 72],
        ];

        foreach ($slaPolicies as $policy) {
            SlaPolicy::firstOrCreate(['priority' => $policy['priority']], $policy);
        }

        // Create it_staff role and permission if not exist
        $itStaffPermission = Permission::firstOrCreate(['name' => 'access_it_panel']);
        $itStaffRole = Role::firstOrCreate(['name' => 'it_staff']);
        $itStaffRole->givePermissionTo($itStaffPermission);

        // Also give it_staff access to admin panel for navigation visibility
        $adminPanelPermission = Permission::firstOrCreate(['name' => 'access_admin_panel']);

        // Create IT Support user
        $itUser = User::firstOrCreate(
            ['email' => 'it@remoteraven.com'],
            [
                'name' => 'IT Support',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $itUser->assignRole('it_staff');

        // Seed ticket categories
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

        // Create 10 sample tickets using existing staff users
        $staffUsers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'staff'))
            ->take(5)
            ->get();

        if ($staffUsers->isEmpty()) {
            return;
        }

        $categoryIds = TicketCategory::pluck('id')->toArray();
        $statuses = [
            TicketStatus::Open,
            TicketStatus::InProgress,
            TicketStatus::OnHold,
            TicketStatus::Resolved,
            TicketStatus::Closed,
        ];
        $priorities = TicketPriority::cases();

        for ($i = 0; $i < 10; $i++) {
            $requester = $staffUsers->random();
            $status = $statuses[$i % count($statuses)];
            $priority = $priorities[$i % count($priorities)];
            $categoryId = $categoryIds[array_rand($categoryIds)];

            $ticket = Ticket::create([
                'category_id' => $categoryId,
                'requester_id' => $requester->id,
                'assignee_id' => $itUser->id,
                'title' => "Sample ticket #{$i}: ".fake()->sentence(5),
                'description' => fake()->paragraphs(2, true),
                'status' => $status->value,
                'priority' => $priority->value,
                'resolved_at' => in_array($status, [TicketStatus::Resolved, TicketStatus::Closed])
                    ? now()->subHours(rand(1, 48))
                    : null,
            ]);
        }
    }
}
