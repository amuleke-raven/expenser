<?php

namespace App\Filament\Staff\Resources\PendingPaymentResource\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Staff\Resources\PendingPaymentResource;
use App\Models\PendingPayment;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPendingPayments extends ListRecords
{
    protected static string $resource = PendingPaymentResource::class;

    public array $selectedTableRecordIds = [];

    public array $pendingPaymentAmountsUsd = [];

    public function mount(): void
    {
        parent::mount();

        $this->pendingPaymentAmountsUsd = PendingPayment::query()
            ->with('currency')
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Processing->value])
            ->get()
            ->mapWithKeys(fn (PendingPayment $record): array => [
                (string) $record->id => round(
                    (float) $record->amount / max((float) ($record->currency?->conversion_rate ?? 1), 0.000001),
                    2
                ),
            ])
            ->all();
    }

    public function getTableHeader(): ?View
    {
        return view('filament.pending-payments-header');
    }
}
