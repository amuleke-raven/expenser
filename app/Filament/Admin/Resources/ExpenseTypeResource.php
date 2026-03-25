<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ExpenseTypeResource\Pages;
use App\Filament\Admin\Resources\ExpenseTypeResource\RelationManagers\ExpenseRulesRelationManager;
use App\Models\ExpenseGroup;
use App\Models\ExpenseType;
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

class ExpenseTypeResource extends Resource
{
    protected static ?string $model = ExpenseType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Expenses';

    protected static ?string $modelLabel = 'Expense Type';

    protected static ?string $pluralModelLabel = 'Expense Types';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->nullable()
                ->columnSpanFull(),

            Select::make('expense_group_id')
                ->label('Expense Group')
                ->options(ExpenseGroup::query()->pluck('name', 'id'))
                ->required()
                ->searchable(),

            Toggle::make('requires_approval')
                ->label('Requires Approval')
                ->live(),

            Toggle::make('requires_attachment')
                ->label('Requires Attachment'),

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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('expenseGroup.name')
                    ->label('Group'),

                IconColumn::make('requires_approval')
                    ->label('Approval')
                    ->boolean(),

                IconColumn::make('requires_attachment')
                    ->label('Attachment')
                    ->boolean(),

                TextColumn::make('workflow.name')
                    ->label('Workflow'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ExpenseRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseTypes::route('/'),
            'create' => Pages\CreateExpenseType::route('/create'),
            'edit' => Pages\EditExpenseType::route('/{record}/edit'),
        ];
    }
}
