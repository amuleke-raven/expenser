<?php

namespace App\Filament\Admin\Resources\SupportedPaymentMethods\Pages;

use App\Filament\Admin\Resources\SupportedPaymentMethods\SupportedPaymentMethodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportedPaymentMethod extends EditRecord
{
    protected static string $resource = SupportedPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
