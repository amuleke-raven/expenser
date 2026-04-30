<?php

namespace App\Filament\Staff\Pages;

use App\Enums\RecipientStatus;
use App\Enums\RewardStatus;
use App\Models\RewardRecipient;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class MyRewardsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'My Disbursements';

    protected static ?string $title = 'My Disbursements';

    protected string $view = 'filament.staff.pages.my-rewards-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RewardRecipient::query()
                    ->where('user_id', auth()->id())
                    ->whereHas('reward', fn ($q) => $q->where('status', RewardStatus::Approved)->orWhere('status', RewardStatus::Paid))
                    ->with(['reward.rewardType', 'reward.currency', 'reward.project'])
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

                TextColumn::make('reward.project.name')->label('Project')->default('—'),

                TextColumn::make('reward.currency.code')->label('Currency')->default('—'),

                TextColumn::make('reward.payout_date')->date()->label('Payout Date')->default('—'),

                TextColumn::make('notified_at')->dateTime()->label('Notified'),
            ])
            ->actions([
                Action::make('viewMessage')
                    ->label('Message')
                    ->icon(Heroicon::EnvelopeOpen)
                    ->color('info')
                    ->modalHeading('Disbursement Message')
                    ->modalContent(fn (RewardRecipient $record) => view('filament.staff.pages.components.custom-message-modal', [
                        'message' => $record->reward->custom_message,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(fn (RewardRecipient $record): bool => filled($record->reward->custom_message)),
            ]);
    }
}
