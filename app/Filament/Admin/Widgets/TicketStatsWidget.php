<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TicketStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $totalOpen = Ticket::query()
            ->whereNotIn('status', [
                TicketStatus::Closed->value,
                TicketStatus::Cancelled->value,
                TicketStatus::Resolved->value,
            ])
            ->count();

        $avgResolutionHours = Ticket::query()
            ->where('status', TicketStatus::Resolved->value)
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at'])
            ->avg(fn (Ticket $t) => $t->created_at->diffInHours($t->resolved_at));

        $slaCompliancePercent = $this->calculateSlaCompliance();

        $unassigned = Ticket::query()
            ->whereNull('assignee_id')
            ->whereNotIn('status', [
                TicketStatus::Closed->value,
                TicketStatus::Cancelled->value,
                TicketStatus::Resolved->value,
            ])
            ->count();

        return [
            Stat::make('Open Tickets', $totalOpen)
                ->icon('heroicon-o-ticket')
                ->color('info'),

            Stat::make('Avg Resolution Time', round((float) $avgResolutionHours, 1).'h')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('SLA Compliance (This Month)', $slaCompliancePercent.'%')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Unassigned Tickets', $unassigned)
                ->icon('heroicon-o-user-minus')
                ->color('danger'),
        ];
    }

    private function calculateSlaCompliance(): int
    {
        $total = Ticket::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($total === 0) {
            return 100;
        }

        $breached = Ticket::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('sla_breached', true)
            ->count();

        return (int) round((($total - $breached) / $total) * 100);
    }
}
