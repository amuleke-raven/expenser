<?php

namespace App\Filament\Staff\Resources\Expenses\Schemas;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\Tag;
use App\Models\UserPaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0.01),
                Select::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::query()->active()->pluck('code', 'id'))
                    ->required(),
                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::query()->active()->pluck('name', 'id'))
                    ->required(),
                DatePicker::make('expense_date')
                    ->required()
                    ->maxDate(now()),
                FileUpload::make('receipt_path')
                    ->label('Receipt')
                    ->visibility('public')
                    ->directory('receipts'),
                Select::make('merchant_id')
                    ->label('Merchant')
                    ->options(Merchant::query()->active()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                Select::make('tags')
                    ->label('Tags')
                    ->options(Tag::query()->active()->pluck('name', 'id'))
                    ->multiple()
                    ->relationship('tags', 'name')
                    ->nullable(),
                Select::make('preferred_payment_method_id')
                    ->label('Preferred Payment Method')
                    ->options(fn (): array => UserPaymentMethod::query()
                        ->where('user_id', auth()->id())
                        ->pluck('label', 'id')
                        ->toArray())
                    ->visible(fn (): bool => UserPaymentMethod::query()
                        ->where('user_id', auth()->id())
                        ->count() > 1)
                    ->nullable(),
            ]);
    }
}
