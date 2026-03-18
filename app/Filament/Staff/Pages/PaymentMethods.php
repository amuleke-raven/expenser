<?php

namespace App\Filament\Staff\Pages;

use App\Enums\PaymentMethodType;
use App\Models\SupportedPaymentMethod;
use App\Models\UserPaymentMethod;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentMethods extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.staff.pages.payment-methods';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Payment Methods';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => UserPaymentMethod::query()->where('user_id', auth()->id()))
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethodType $state): string => $state->label()),
                TextColumn::make('label')
                    ->searchable(),
                IconColumn::make('is_default')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->model(UserPaymentMethod::class)
                    ->schema($this->paymentMethodFormSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema($this->paymentMethodFormSchema()),
                DeleteAction::make(),
            ]);
    }

    /** @return array<int, mixed> */
    private function paymentMethodFormSchema(): array
    {
        return [
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
        ];
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

    protected function getHeaderActions(): array
    {
        return [];
    }
}
