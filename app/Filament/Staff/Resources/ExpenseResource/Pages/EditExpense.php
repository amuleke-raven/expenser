<?php

namespace App\Filament\Staff\Resources\ExpenseResource\Pages;

use App\Enums\ExpenseStatus;
use App\Filament\Staff\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    /** @var array<string> */
    private array $savedAttachmentFiles = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Expense $record): bool => $record->status === ExpenseStatus::Draft),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['attachment_files'] = $this->record->attachments()->pluck('path')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->savedAttachmentFiles = $data['attachment_files'] ?? [];
        unset($data['attachment_files']);

        return $data;
    }

    protected function afterSave(): void
    {
        $existingPaths = $this->record->attachments()->pluck('path')->all();
        $currentPaths = $this->savedAttachmentFiles;

        $toDelete = array_diff($existingPaths, $currentPaths);
        $toAdd = array_diff($currentPaths, $existingPaths);

        if ($toDelete) {
            $this->record->attachments()->whereIn('path', $toDelete)->delete();
        }

        foreach ($toAdd as $path) {
            ExpenseAttachment::create([
                'expense_id' => $this->record->id,
                'path' => $path,
                'original_name' => basename($path),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
