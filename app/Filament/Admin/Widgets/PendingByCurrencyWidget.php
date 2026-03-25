<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\PaymentStatus;
use App\Models\PendingPayment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class PendingByCurrencyWidget extends BaseWidget
{
    protected static ?string $heading = 'Currency Exposure';

    protected static ?int $sort = 5;

    public function getTableRecordKey(Model|array $record): string
    {
        return (string) (is_array($record) ? $record['currency_id'] : $record->currency_id);
    }

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['accountant', 'super_admin']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PendingPayment::query()
                    ->where('status', PaymentStatus::Pending)
                    ->selectRaw('currency_id, count(*) as payment_count, sum(amount) as total_local')
                    ->groupBy('currency_id')
                    ->with('currency')
            )
            ->columns([
                TextColumn::make('currency.code')->label('Currency'),
                TextColumn::make('payment_count')->label('Count'),
                TextColumn::make('total_local')
                    ->label('Total (Local)')
                    ->formatStateUsing(fn ($record) => ($record->currency?->symbol ?? '').number_format((float) $record->total_local, 2)
                    ),
                TextColumn::make('total_usd')
                    ->label('Total (USD)')
                    ->getStateUsing(fn ($record): string => '$'.number_format(
                        (float) $record->total_local / max((float) ($record->currency?->conversion_rate ?? 1), 0.000001),
                        2
                    )),
            ]);
    }
}
