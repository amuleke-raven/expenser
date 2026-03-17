<?php

namespace App\Filament\Admin\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('symbol')
                    ->required(),
                TextInput::make('exchange_rate')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('is_base')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
