<?php

namespace App\Filament\Admin\Resources\RewardResource\RelationManagers;

use App\Enums\RecipientStatus;
use App\Models\RewardRecipient;
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
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('user_id')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
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
            ]);
    }
}
