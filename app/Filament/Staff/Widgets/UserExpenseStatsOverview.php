<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserExpenseStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();

        $totalCount = Expense::query()
            ->forUser($userId)
            ->count();

        $totalAmount = Expense::query()
            ->forUser($userId)
            ->sum('amount');

        return [
            Stat::make('Total Expenses Raised', number_format($totalCount)),
            Stat::make('Total Amount', number_format((float) $totalAmount, 2)),
        ];
    }
}
