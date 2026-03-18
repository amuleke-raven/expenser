<?php

namespace App\Filament\Staff\Pages;

use App\Filament\Staff\Widgets\UserExpensesByCategoryChart;
use App\Filament\Staff\Widgets\UserExpensesByDateChart;
use App\Filament\Staff\Widgets\UserExpenseStatsOverview;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected string $view = 'filament.staff.pages.dashboard';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -1;

    public function getWidgets(): array
    {
        return [
            UserExpenseStatsOverview::class,
            UserExpensesByCategoryChart::class,
            UserExpensesByDateChart::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
