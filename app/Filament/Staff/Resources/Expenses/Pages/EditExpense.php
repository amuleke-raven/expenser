<?php

namespace App\Filament\Staff\Resources\Expenses\Pages;

use App\Filament\Staff\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function authorizeAccess(): void
    {
        /** @var Expense $record */
        $record = $this->getRecord();

        abort_unless($record->isDraft(), 403, 'Only draft expenses can be edited.');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
