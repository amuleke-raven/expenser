<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\ExpenseStatus;
use App\Enums\WorkflowStepStatus;
use App\Models\Expense;
use App\Models\ExpenseWorkflowStep;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpenseStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalSubmitted = Expense::query()
            ->whereNot('status', ExpenseStatus::Draft->value)
            ->count();

        $totalAmount = Expense::query()
            ->whereIn('status', [ExpenseStatus::Approved->value, ExpenseStatus::Processing->value, ExpenseStatus::Paid->value])
            ->sum('amount');

        $pendingApprovals = ExpenseWorkflowStep::query()
            ->where('status', WorkflowStepStatus::Pending->value)
            ->count();

        $paidThisMonth = Expense::query()
            ->where('status', ExpenseStatus::Paid->value)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('amount');

        return [
            Stat::make('Total Submitted', number_format($totalSubmitted)),
            Stat::make('Total Approved Amount', '$'.number_format((float) $totalAmount, 2)),
            Stat::make('Pending Approvals', number_format($pendingApprovals)),
            Stat::make('Paid This Month', '$'.number_format((float) $paidThisMonth, 2)),
        ];
    }
}
