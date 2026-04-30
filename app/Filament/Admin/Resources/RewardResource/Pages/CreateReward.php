<?php

namespace App\Filament\Admin\Resources\RewardResource\Pages;

use App\Filament\Admin\Resources\RewardResource;
use App\Models\RewardAttachment;
use Filament\Resources\Pages\CreateRecord;

class CreateReward extends CreateRecord
{
    protected static string $resource = RewardResource::class;

    /** @var array<string> */
    private array $pendingAttachmentFiles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['initiated_by'] = auth()->id();
        $this->pendingAttachmentFiles = $data['attachment_files'] ?? [];
        unset($data['attachment_files']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->pendingAttachmentFiles as $path) {
            RewardAttachment::create([
                'reward_id' => $this->record->id,
                'path' => $path,
                'original_name' => basename($path),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
