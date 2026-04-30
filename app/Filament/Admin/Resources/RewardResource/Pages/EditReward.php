<?php

namespace App\Filament\Admin\Resources\RewardResource\Pages;

use App\Enums\RewardStatus;
use App\Filament\Admin\Resources\RewardResource;
use App\Models\Reward;
use App\Models\RewardAttachment;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReward extends EditRecord
{
    protected static string $resource = RewardResource::class;

    /** @var array<string> */
    private array $savedAttachmentFiles = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Reward $record): bool => $record->status === RewardStatus::Draft),
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
            RewardAttachment::create([
                'reward_id' => $this->record->id,
                'path' => $path,
                'original_name' => basename($path),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
