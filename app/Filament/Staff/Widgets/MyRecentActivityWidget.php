<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Expense;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MyRecentActivityWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Activity';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // We'll use a union-style approach via a base model query
                Expense::query()
                    ->where('user_id', auth()->id())
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->getStateUsing(fn ($record) => 'Expense')
                    ->color('info'),

                TextColumn::make('ref')
                    ->label('Ref')
                    ->getStateUsing(fn (Expense $record): string => $record->ref()),

                TextColumn::make('total_amount')->money()->label('Amount'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($record) => $record->status->color()),

                TextColumn::make('created_at')->dateTime()->label('Date'),
            ]);
    }
}
