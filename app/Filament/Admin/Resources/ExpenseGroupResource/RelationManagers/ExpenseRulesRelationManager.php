<?php

namespace App\Filament\Admin\Resources\ExpenseGroupResource\RelationManagers;

use App\Enums\RuleDimension;
use App\Enums\RuleOperator;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpenseRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'rules';

    protected static ?string $title = 'Expense Rules';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('dimension')
                ->options(collect(RuleDimension::cases())->mapWithKeys(
                    fn ($case) => [$case->value => $case->label()]
                ))
                ->required(),

            Select::make('operator')
                ->options(collect(RuleOperator::cases())->mapWithKeys(
                    fn ($case) => [$case->value => $case->label()]
                ))
                ->required(),

            Textarea::make('value')
                ->required()
                ->helperText('JSON value e.g. {"amount": 500} or {"countries": [1, 2]}')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dimension')
                    ->badge()
                    ->color(fn (RuleDimension $state): string => $state->color()),
                TextColumn::make('operator')
                    ->badge(),
                TextColumn::make('value')
                    ->formatStateUsing(fn ($state) => json_encode($state)),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
