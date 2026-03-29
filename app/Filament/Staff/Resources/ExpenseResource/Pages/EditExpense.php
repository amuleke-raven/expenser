<?php

namespace App\Filament\Staff\Resources\ExpenseResource\Pages;

use App\Enums\ExpenseStatus;
use App\Filament\Staff\Resources\ExpenseResource;
use App\Models\Expense;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Expense $record): bool => $record->status === ExpenseStatus::Draft),
        ];
    }
}
