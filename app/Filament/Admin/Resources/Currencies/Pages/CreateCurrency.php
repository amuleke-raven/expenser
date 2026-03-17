<?php

namespace App\Filament\Admin\Resources\Currencies\Pages;

use App\Filament\Admin\Resources\Currencies\CurrencyResource;
use App\Models\Currency;
use Filament\Resources\Pages\CreateRecord;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record->is_base) {
            Currency::query()
                ->where('id', '!=', $record->id)
                ->update(['is_base' => false]);
        }
    }
}
