<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ExpenseGroupResource\Pages;
use App\Filament\Admin\Resources\ExpenseGroupResource\RelationManagers\AssignedRolesRelationManager;
use App\Filament\Admin\Resources\ExpenseGroupResource\RelationManagers\ExpenseRulesRelationManager;
use App\Filament\Admin\Resources\ExpenseGroupResource\RelationManagers\ExpenseTypesRelationManager;
use App\Models\ExpenseGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

class ExpenseGroupResource extends Resource
{
    protected static ?string $model = ExpenseGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Expenses';

    protected static ?string $modelLabel = 'Expense Group';

    protected static ?string $pluralModelLabel = 'Expense Groups';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Expense Group Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->nullable()
                        ->columnSpanFull(),

                    Toggle::make('is_default')
                        ->label('Default Group'),
                ])->columnSpanFull(),
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

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                TextColumn::make('expense_types_count')
                    ->counts('expenseTypes')
                    ->label('Types'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ExpenseTypesRelationManager::class,
            AssignedRolesRelationManager::class,
            ExpenseRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseGroups::route('/'),
            'create' => Pages\CreateExpenseGroup::route('/create'),
            'edit' => Pages\EditExpenseGroup::route('/{record}/edit'),
        ];
    }
}
