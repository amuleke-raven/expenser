<?php

namespace App\Filament\Admin\Resources\Expenses\Schemas;

use App\Enums\ExpenseStatus;
use Filament\Forms\Components\DatePicker;
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
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                Select::make('merchant_id')
                    ->relationship('merchant', 'name'),
                Select::make('workflow_id')
                    ->relationship('workflow', 'name'),
                Select::make('preferred_payment_method_id')
                    ->relationship('preferredPaymentMethod', 'id'),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                DatePicker::make('expense_date')
                    ->required(),
                TextInput::make('receipt_path'),
                Select::make('status')
                    ->options(ExpenseStatus::class)
                    ->default('draft')
                    ->required(),
                TextInput::make('rejection_reason'),
                Textarea::make('rejection_comment')
                    ->columnSpanFull(),
            ]);
    }
}
