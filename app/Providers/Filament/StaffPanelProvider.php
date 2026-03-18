<?php

namespace App\Providers\Filament;

use App\Filament\Staff\Pages\Dashboard;
use App\Filament\Staff\Widgets\UserExpensesByCategoryChart;
use App\Filament\Staff\Widgets\UserExpensesByDateChart;
use App\Filament\Staff\Widgets\UserExpenseStatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StaffPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('staff')
            ->path('app')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Staff/Resources'), for: 'App\Filament\Staff\Resources')
            ->discoverPages(in: app_path('Filament/Staff/Pages'), for: 'App\Filament\Staff\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Staff/Widgets'), for: 'App\Filament\Staff\Widgets')
            ->widgets([
                AccountWidget::class,
                UserExpenseStatsOverview::class,
                UserExpensesByCategoryChart::class,
                UserExpensesByDateChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
