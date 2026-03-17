<?php

namespace App\Filament\Admin\Resources\Expenses;

use App\Enums\ExpenseStatus;
use App\Filament\Admin\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Admin\Resources\Expenses\Pages\ViewExpense;
use App\Filament\Admin\Resources\Expenses\Schemas\ExpenseForm;
use App\Filament\Admin\Resources\Expenses\Tables\ExpensesTable;
use App\Models\Expense;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return ExpenseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('user.name'),
                TextEntry::make('amount')
                    ->formatStateUsing(fn ($state, Expense $record): string => number_format((float) $state, 2).' '.$record->currency?->code),
                TextEntry::make('category.name'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (ExpenseStatus $state): string => $state->color())
                    ->formatStateUsing(fn (ExpenseStatus $state): string => $state->label()),
                TextEntry::make('expense_date')
                    ->date(),
                TextEntry::make('merchant.name'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('rejection_reason'),
                TextEntry::make('rejection_comment'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTable::configure($table);
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
            'index' => ListExpenses::route('/'),
            'view' => ViewExpense::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
