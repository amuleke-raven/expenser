<?php

namespace App\Filament\Admin\Resources\CurrencyResource\Pages;

use App\Filament\Admin\Resources\CurrencyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCurrency extends EditRecord
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
