<?php

namespace App\Filament\Admin\Resources\Rules\Schemas;

use App\Enums\RuleKey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('key')
                    ->options(collect(RuleKey::cases())->mapWithKeys(fn (RuleKey $k) => [$k->value => $k->label()]))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('value')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
