<?php

namespace App\Filament\Admin\Resources\ExpenseGroupResource\Pages;

use App\Filament\Admin\Resources\ExpenseGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpenseGroup extends EditRecord
{
    protected static string $resource = ExpenseGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
