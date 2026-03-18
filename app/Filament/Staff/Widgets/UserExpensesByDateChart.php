<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserExpensesByDateChart extends ChartWidget
{
    protected ?string $heading = 'Expenses by Date';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        $filters = [];

        for ($i = 0; $i < 12; $i++) {
            $month = now()->subMonths($i);
            $filters[$month->format('Y-m')] = $month->format('F Y');
        }

        return $filters;
    }

    protected function getData(): array
    {
        $yearMonth = $this->filter ?? now()->format('Y-m');
        [$year, $month] = explode('-', $yearMonth);

        $data = Expense::query()
            ->select(DB::raw("strftime('%Y-%m-%d', expense_date) as date"), DB::raw('COUNT(*) as count'))
            ->forUser(auth()->id())
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return [
            'datasets' => [
                [
                    'label' => 'Expenses',
                    'data' => $data->values()->toArray(),
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
