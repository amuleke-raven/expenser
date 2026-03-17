<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopSpendersWidget extends TableWidget
{
    protected static ?string $heading = 'Top Spenders';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()
                ->withSum('expenses', 'amount')
                ->orderByDesc('expenses_sum_amount')
                ->limit(10))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('expenses_sum_amount')
                    ->label('Total Expenses')
                    ->numeric(decimalPlaces: 2)
                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
            ]);
    }
}
