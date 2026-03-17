<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpensesByMonthChart extends ChartWidget
{
    protected ?string $heading = 'Expenses Submitted by Month';

    protected function getData(): array
    {
        $data = Expense::query()
            ->select(DB::raw("strftime('%Y-%m', created_at) as month"), DB::raw('COUNT(*) as count'))
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $months = collect(range(1, 12))->map(fn ($m) => now()->startOfYear()->addMonths($m - 1)->format('Y-m'));
        $labels = $months->map(fn ($m) => now()->startOfYear()->addMonths((int) substr($m, 5, 2) - 1)->format('M'));

        return [
            'datasets' => [
                [
                    'label' => 'Expenses',
                    'data' => $months->map(fn ($m) => $data->get($m, 0))->values()->toArray(),
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
