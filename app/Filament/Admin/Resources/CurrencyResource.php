<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $modelLabel = 'Currency';

    protected static ?string $pluralModelLabel = 'Currencies';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Currency Details')
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(3)
                        ->label('Code'),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('symbol')
                        ->required()
                        ->maxLength(10),

                    TextInput::make('conversion_rate')
                        ->required()
                        ->numeric()
                        ->label('Conversion Rate'),

                    Toggle::make('is_base')
                        ->label('Is Base Currency')
                        ->helperText('Only one currency can be the base'),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('symbol'),

                TextColumn::make('conversion_rate')
                    ->label('Rate')
                    ->numeric(decimalPlaces: 6),

                IconColumn::make('is_base')
                    ->label('Base')
                    ->boolean(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
