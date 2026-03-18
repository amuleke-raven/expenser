<?php

namespace App\Filament\Admin\Resources\SupportedPaymentMethods\Pages;

use App\Filament\Admin\Resources\SupportedPaymentMethods\SupportedPaymentMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportedPaymentMethods extends ListRecords
{
    protected static string $resource = SupportedPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
