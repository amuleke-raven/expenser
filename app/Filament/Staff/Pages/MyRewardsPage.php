<?php

namespace App\Filament\Staff\Pages;

use App\Enums\RecipientStatus;
use App\Models\RewardRecipient;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class MyRewardsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'My Rewards';

    protected string $view = 'filament.staff.pages.my-rewards-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RewardRecipient::query()
                    ->where('user_id', auth()->id())
                    ->with(['reward.rewardType', 'reward.currency'])
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reward.ref')
                    ->label('Ref')
                    ->getStateUsing(fn (RewardRecipient $record): string => $record->reward?->ref() ?? '—'),

                TextColumn::make('reward.rewardType.name')->label('Type'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn (RewardRecipient $record): string => ($record->reward?->currency?->symbol ?? '').
                        number_format((float) ($record->reward?->amount ?? 0), 2)
                    ),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (RecipientStatus $state): string => $state->color()),

                TextColumn::make('notified_at')->dateTime()->label('Notified'),
            ]);
    }
}
