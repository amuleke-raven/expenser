<?php

namespace App\Filament\Admin\Resources\ExpenseResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LineItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'lineItems';

    protected static ?string $title = 'Line Items';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('description')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('description'),
                TextColumn::make('quantity')->numeric(decimalPlaces: 2),
                TextColumn::make('unit_price')->money()->label('Unit Price'),
                TextColumn::make('total')->money(),
            ]);
    }
}
