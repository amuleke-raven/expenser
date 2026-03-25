<?php

namespace App\Filament\Admin\Resources\ExpenseTypeResource\Pages;

use App\Filament\Admin\Resources\ExpenseTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseTypes extends ListRecords
{
    protected static string $resource = ExpenseTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
