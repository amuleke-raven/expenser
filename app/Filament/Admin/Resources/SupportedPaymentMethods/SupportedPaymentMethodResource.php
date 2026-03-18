<?php

namespace App\Filament\Admin\Resources\SupportedPaymentMethods;

use App\Filament\Admin\Resources\SupportedPaymentMethods\Pages\CreateSupportedPaymentMethod;
use App\Filament\Admin\Resources\SupportedPaymentMethods\Pages\EditSupportedPaymentMethod;
use App\Filament\Admin\Resources\SupportedPaymentMethods\Pages\ListSupportedPaymentMethods;
use App\Filament\Admin\Resources\SupportedPaymentMethods\Schemas\SupportedPaymentMethodForm;
use App\Filament\Admin\Resources\SupportedPaymentMethods\Tables\SupportedPaymentMethodsTable;
use App\Models\SupportedPaymentMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportedPaymentMethodResource extends Resource
{
    protected static ?string $model = SupportedPaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Payment Methods';

    protected static ?string $modelLabel = 'Payment Method';

    public static function form(Schema $schema): Schema
    {
        return SupportedPaymentMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportedPaymentMethodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportedPaymentMethods::route('/'),
            'create' => CreateSupportedPaymentMethod::route('/create'),
            'edit' => EditSupportedPaymentMethod::route('/{record}/edit'),
        ];
    }
}
