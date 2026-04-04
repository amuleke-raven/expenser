<?php

namespace App\Filament\Admin\Resources\RewardResource\RelationManagers;

use App\Enums\RecipientStatus;
use App\Enums\RecipientType;
use App\Enums\RewardStatus;
use App\Models\Reward;
use App\Models\RewardRecipient;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RewardRecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Recipients';

    public function isReadOnly(): bool
    {
        /** @var Reward $reward */
        $reward = $this->getOwnerRecord();

        return in_array($reward->status, [RewardStatus::Approved, RewardStatus::Rejected, RewardStatus::Paid]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('user_id')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var Reward $reward */
        $reward = $this->getOwnerRecord();
        $canModify = ! $this->isReadOnly();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->state(fn (RewardRecipient $record): string => $record->user?->name ?? $record->name ?? '—'),
                TextColumn::make('email')
                    ->label('Email')
                    ->state(fn (RewardRecipient $record): string => $record->user?->email ?? $record->email ?? '—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (RecipientStatus $state): string => $state->color()),
                TextColumn::make('notified_at')->dateTime()->label('Notified'),
                TextColumn::make('paid_at')->dateTime()->label('Paid'),
            ])
            ->headerActions($canModify ? [
                Action::make('add_recipient')
                    ->label('Add Recipient')
                    ->icon('heroicon-o-user-plus')
                    ->form($reward->recipient_type === RecipientType::External
                        ? [
                            Repeater::make('recipients')
                                ->label('External Recipients')
                                ->schema([
                                    TextInput::make('name')->required()->label('Name'),
                                    TextInput::make('email')->email()->required()->label('Email'),
                                ])
                                ->minItems(1)
                                ->addActionLabel('Add Recipient'),
                        ]
                        : [
                            Select::make('user_ids')
                                ->label('Select Recipients')
                                ->options(User::query()->pluck('name', 'id'))
                                ->multiple()
                                ->searchable()
                                ->required(),
                        ]
                    )
                    ->action(function (array $data) use ($reward) {
                        if ($reward->recipient_type === RecipientType::External) {
                            foreach ($data['recipients'] as $recipient) {
                                RewardRecipient::firstOrCreate(
                                    ['reward_id' => $reward->id, 'email' => $recipient['email']],
                                    ['name' => $recipient['name']],
                                );
                            }
                        } else {
                            foreach ($data['user_ids'] as $userId) {
                                RewardRecipient::firstOrCreate([
                                    'reward_id' => $reward->id,
                                    'user_id' => $userId,
                                ]);
                            }
                        }
                    }),
            ] : [])
            ->actions($canModify ? [
                DeleteAction::make()
                    ->visible(fn (RewardRecipient $record): bool => $record->status === RecipientStatus::Pending),
            ] : []);
    }
}
