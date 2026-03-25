<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use Filament\Widgets\ChartWidget;

class ExpensesByStatusWidget extends ChartWidget
{
    protected ?string $heading = 'Expenses by Status';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $grouped = Expense::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        foreach (ExpenseStatus::cases() as $case) {
            $labels[] = $case->label();
            $data[] = $grouped[$case->value] ?? 0;
            $colors[] = $this->filamentColorToHex($case->color());
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    private function filamentColorToHex(string $color): string
    {
        return match ($color) {
            'gray' => '#6b7280',
            'info' => '#3b82f6',
            'warning' => '#f59e0b',
            'success' => '#10b981',
            'danger' => '#ef4444',
            default => '#6b7280',
        };
    }
}
