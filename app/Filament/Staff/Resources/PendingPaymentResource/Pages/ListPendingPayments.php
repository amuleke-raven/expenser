<?php

namespace App\Filament\Staff\Resources\PendingPaymentResource\Pages;

use App\Filament\Staff\Resources\PendingPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListPendingPayments extends ListRecords
{
    protected static string $resource = PendingPaymentResource::class;
}
