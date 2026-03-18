<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserExpensesByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Expenses by Category';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        $years = Expense::query()
            ->forUser(auth()->id())
            ->selectRaw("strftime('%Y', expense_date) as year")
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year', 'year')
            ->toArray();

        return $years ?: [(string) now()->year => (string) now()->year];
    }

    protected function getData(): array
    {
        $year = $this->filter ?? now()->year;

        $data = Expense::query()
            ->select('categories.name', DB::raw('COUNT(*) as count'))
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->forUser(auth()->id())
            ->whereYear('expense_date', $year)
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Expenses',
                    'data' => $data->pluck('count')->toArray(),
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
