<?php

namespace App\Filament\Admin\Resources\Rules;

use App\Filament\Admin\Resources\Rules\Pages\CreateRule;
use App\Filament\Admin\Resources\Rules\Pages\EditRule;
use App\Filament\Admin\Resources\Rules\Pages\ListRules;
use App\Filament\Admin\Resources\Rules\Schemas\RuleForm;
use App\Filament\Admin\Resources\Rules\Tables\RulesTable;
use App\Models\Rule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RuleResource extends Resource
{
    protected static ?string $model = Rule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return RuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRules::route('/'),
            'create' => CreateRule::route('/create'),
            'edit' => EditRule::route('/{record}/edit'),
        ];
    }
}
