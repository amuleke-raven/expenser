<?php

namespace App\Filament\Admin\Resources\SupportedPaymentMethods\Pages;

use App\Filament\Admin\Resources\SupportedPaymentMethods\SupportedPaymentMethodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportedPaymentMethod extends CreateRecord
{
    protected static string $resource = SupportedPaymentMethodResource::class;
}
