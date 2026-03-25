<?php

namespace App\Filament\Admin\Resources;

use App\Enums\PaymentMethodType;
use App\Filament\Admin\Resources\PaymentMethodResource\Pages;
use App\Models\Country;
use App\Models\PaymentMethod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $modelLabel = 'Payment Method';

    protected static ?string $pluralModelLabel = 'Payment Methods';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            Select::make('type')
                ->options(collect(PaymentMethodType::cases())->mapWithKeys(
                    fn ($case) => [$case->value => $case->label()]
                ))
                ->required(),

            Toggle::make('is_global')
                ->label('Available Globally')
                ->live()
                ->default(true),

            Select::make('country_id')
                ->label('Country')
                ->options(Country::query()->pluck('name', 'id'))
                ->searchable()
                ->visible(fn (Get $get): bool => ! $get('is_global'))
                ->nullable(),
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

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (PaymentMethodType $state): string => $state->color()),

                IconColumn::make('is_global')
                    ->label('Global')
                    ->boolean(),

                TextColumn::make('country.name')
                    ->label('Country'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
