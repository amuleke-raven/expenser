<?php

namespace App\Filament\Admin\Resources\ExpenseTypeResource\Pages;

use App\Filament\Admin\Resources\ExpenseTypeResource;
use App\Models\ExpenseType;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExpenseType extends EditRecord
{
    protected static string $resource = ExpenseTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, ExpenseType $record): void {
                    if ($record->expenses()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete expense type')
                            ->body('This expense type has associated expenses. Remove or reassign them first.')
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
