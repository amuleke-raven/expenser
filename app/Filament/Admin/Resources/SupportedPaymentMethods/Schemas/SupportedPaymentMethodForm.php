<?php

namespace App\Filament\Admin\Resources\SupportedPaymentMethods\Schemas;

use App\Enums\PaymentMethodType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupportedPaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(collect(PaymentMethodType::cases())->mapWithKeys(fn (PaymentMethodType $t) => [$t->value => $t->label()]))
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
