<?php

namespace App\Filament\Staff\Resources\ExpenseResource\Pages;

use App\Filament\Staff\Resources\ExpenseResource;
use App\Models\ExpenseAttachment;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    /** @var array<string> */
    private array $pendingAttachmentFiles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $this->pendingAttachmentFiles = $data['attachment_files'] ?? [];
        unset($data['attachment_files']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->pendingAttachmentFiles as $path) {
            ExpenseAttachment::create([
                'expense_id' => $this->record->id,
                'path' => $path,
                'original_name' => basename($path),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
