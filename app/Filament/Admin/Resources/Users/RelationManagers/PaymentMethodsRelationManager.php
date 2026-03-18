<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Enums\PaymentMethodType;
use App\Models\SupportedPaymentMethod;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentMethodsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentMethods';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(
                        SupportedPaymentMethod::query()
                            ->active()
                            ->get()
                            ->mapWithKeys(fn (SupportedPaymentMethod $m) => [$m->type->value => $m->type->label()])
                    )
                    ->required()
                    ->live(),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_default'),
                ...$this->detailFieldComponents(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethodType $state): string => $state->label()),
                TextColumn::make('label')
                    ->searchable(),
                IconColumn::make('is_default')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @return array<int, TextInput> */
    private function detailFieldComponents(): array
    {
        $components = [];

        foreach (PaymentMethodType::cases() as $type) {
            foreach ($type->detailFields() as $field) {
                $components[] = TextInput::make("details.{$field['key']}")
                    ->label($field['label'])
                    ->required($field['required'])
                    ->visible(fn (Get $get): bool => $get('type') === $type->value);
            }
        }

        return $components;
    }
}
