<?php

namespace App\Filament\Staff\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\PendingPayment;
use App\Models\RewardRecipient;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()->can('view_finance');
    }

    protected function getStats(): array
    {
        $baseCurrency = Currency::base()->first();
        $symbol = $baseCurrency?->symbol ?? '$';

        $pending = PendingPayment::where('status', PaymentStatus::Pending);
        $pendingCount = $pending->count();
        $pendingSum = $pending->sum('amount');

        $expCount = PendingPayment::where('status', PaymentStatus::Pending)
            ->where('payable_type', Expense::class)->count();
        $rwdCount = PendingPayment::where('status', PaymentStatus::Pending)
            ->where('payable_type', RewardRecipient::class)->count();

        $paidThisMonth = PendingPayment::where('status', PaymentStatus::Paid)
            ->whereMonth('processed_at', now()->month)->sum('amount');

        $failedCount = PendingPayment::where('status', PaymentStatus::Failed)->count();

        return [
            Stat::make('Pending Payments Value', $symbol.number_format($pendingSum, 2)),

            Stat::make('Pending Payments Count', $pendingCount)
                ->description("{$expCount} expenses · {$rwdCount} rewards"),

            Stat::make('Processed This Month', $symbol.number_format($paidThisMonth, 2)),

            Stat::make('Failed Payments', $failedCount)
                ->color('danger'),
        ];
    }
}
