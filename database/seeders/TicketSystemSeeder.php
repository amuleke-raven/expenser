<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSystemSeeder extends Seeder
{
    public function run(): void
    {
        $itUser = User::where('email', 'it@remoteraven.com')->first();
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

            Ticket::create([
                'category_id' => $categoryId,
                'requester_id' => $requester->id,
                'assignee_id' => $itUser?->id,
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
