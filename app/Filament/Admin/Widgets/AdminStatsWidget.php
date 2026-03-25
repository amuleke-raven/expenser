<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\StepActionStatus;
use App\Models\Expense;
use App\Models\Reward;
use App\Models\User;
use App\Models\WorkflowStepAction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $expensesThisMonth = Expense::whereMonth('created_at', now()->month)->get();
        $rewardsThisMonth = Reward::whereMonth('created_at', now()->month)->get();

        return [
            Stat::make('Expenses This Month', $expensesThisMonth->count())
                ->description('$'.number_format($expensesThisMonth->sum('total_amount'), 2)),

            Stat::make('Rewards This Month', $rewardsThisMonth->count())
                ->description('$'.number_format($rewardsThisMonth->sum('amount'), 2)),

            Stat::make('Pending Approvals', WorkflowStepAction::where('status', StepActionStatus::Pending)->count())
                ->color('warning'),

            Stat::make('Active Users', User::count()),
        ];
    }
}
