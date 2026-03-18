<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\ExpensesByCategoryChart;
use App\Filament\Admin\Widgets\ExpensesByMonthChart;
use App\Filament\Admin\Widgets\ExpenseStatsOverview;
use App\Filament\Admin\Widgets\TopSpendersWidget;
use Filament\Pages\Page;

class Analytics extends Page
{
    protected string $view = 'filament.admin.pages.analytics';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Summary';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    public function getWidgets(): array
    {
        return [
            ExpenseStatsOverview::class,
            TopSpendersWidget::class,
            ExpensesByMonthChart::class,
            ExpensesByCategoryChart::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
