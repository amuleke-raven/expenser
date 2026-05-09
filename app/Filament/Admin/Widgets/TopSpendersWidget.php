<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopSpendersWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Spenders This Month';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->withCount(['expenses as expense_count' => fn (Builder $q) => $q->whereMonth('created_at', now()->month),
                    ])
                    ->withSum(['expenses as expense_total' => fn (Builder $q) => $q->whereMonth('created_at', now()->month),
                    ], 'total_amount')
                    ->whereHas('expenses', fn (Builder $q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
                    ->orderByDesc('expense_total')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('expense_count')->label('Count'),
                TextColumn::make('expense_total')->label('Total')->money(),
            ]);
    }
}
