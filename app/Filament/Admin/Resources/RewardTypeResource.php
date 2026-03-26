<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RewardTypeResource\Pages;
use App\Filament\Admin\Resources\RewardTypeResource\RelationManagers\RewardRulesRelationManager;
use App\Models\Currency;
use App\Models\RewardType;
use App\Models\Workflow;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RewardTypeResource extends Resource
{
    protected static ?string $model = RewardType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static string|\UnitEnum|null $navigationGroup = 'Rewards';

    protected static ?string $modelLabel = 'Reward Type';

    protected static ?string $pluralModelLabel = 'Reward Types';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')->required()->maxLength(255),
            Textarea::make('description')->nullable()->columnSpanFull(),
            Toggle::make('is_fixed')->label('Fixed Amount')->live(),
            TextInput::make('fixed_amount')
                ->numeric()
                ->nullable()
                ->visible(fn (Get $get): bool => (bool) $get('is_fixed'))
                ->label('Fixed Amount'),

            Select::make('fixed_currency_id')
                ->label('Fixed Currency')
                ->options(Currency::query()->pluck('code', 'id'))
                ->default(fn () => Currency::where('code', 'USD')->value('id'))
                ->searchable()
                ->nullable()
                ->visible(fn (Get $get): bool => (bool) $get('is_fixed')),
            Toggle::make('is_client_based')->label('Client Based'),
            Toggle::make('requires_approval')->label('Requires Approval')->live(),
            Select::make('workflow_id')
                ->label('Workflow')
                ->options(Workflow::query()->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->visible(fn (Get $get): bool => (bool) $get('requires_approval')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                IconColumn::make('is_fixed')->boolean()->label('Fixed'),
                TextColumn::make('fixed_amount')->money()->label('Fixed Amount'),
                IconColumn::make('is_client_based')->boolean()->label('Client Based'),
                IconColumn::make('requires_approval')->boolean()->label('Approval'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RewardRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRewardTypes::route('/'),
            'create' => Pages\CreateRewardType::route('/create'),
            'edit' => Pages\EditRewardType::route('/{record}/edit'),
        ];
    }
}
