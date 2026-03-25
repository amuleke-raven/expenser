<?php

namespace App\Filament\Staff\Widgets;

use App\Enums\ExpenseStatus;
use App\Enums\RecipientStatus;
use App\Models\Expense;
use App\Models\RewardRecipient;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MyExpenseStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = auth()->id();

        $submittedSum = Expense::forUser($userId)
            ->where('status', ExpenseStatus::Submitted)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        $approvedSum = Expense::forUser($userId)
            ->where('status', ExpenseStatus::Approved)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        $pendingCount = Expense::forUser($userId)
            ->whereIn('status', [ExpenseStatus::UnderReview, ExpenseStatus::Submitted])
            ->count();

        $rewardsSum = RewardRecipient::where('reward_recipients.user_id', $userId)
            ->where('reward_recipients.status', RecipientStatus::Paid)
            ->join('rewards', 'reward_recipients.reward_id', '=', 'rewards.id')
            ->sum('rewards.amount');

        return [
            Stat::make('Submitted This Month', '$'.number_format($submittedSum, 2)),
            Stat::make('Approved This Month', '$'.number_format($approvedSum, 2)),
            Stat::make('Pending Approval', $pendingCount)->color('warning'),
            Stat::make('Rewards Received', '$'.number_format($rewardsSum, 2))->color('success'),
        ];
    }
}
