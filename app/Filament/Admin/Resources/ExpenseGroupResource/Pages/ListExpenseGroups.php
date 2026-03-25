<?php

namespace App\Filament\Admin\Resources\ExpenseGroupResource\Pages;

use App\Filament\Admin\Resources\ExpenseGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseGroups extends ListRecords
{
    protected static string $resource = ExpenseGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
