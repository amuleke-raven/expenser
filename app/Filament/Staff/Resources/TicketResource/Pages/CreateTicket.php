<?php

namespace App\Filament\Staff\Resources\TicketResource\Pages;

use App\Enums\TicketStatus;
use App\Filament\Staff\Resources\TicketResource;
use App\Services\TicketActivityLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requester_id'] = auth()->id();
        $data['status'] = TicketStatus::Open->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $attachmentFiles = $this->data['attachment_files'] ?? [];

        foreach ($attachmentFiles as $path) {
            $fullPath = Storage::disk('public')->path($path);
            $this->record->attachments()->create([
                'user_id' => auth()->id(),
                'filename' => basename($path),
                'path' => $path,
                'mime_type' => mime_content_type($fullPath) ?: 'application/octet-stream',
                'size' => Storage::disk('public')->size($path),
            ]);

            app()->make(TicketActivityLogger::class)->log($this->record, 'attachment_added', [
                'filename' => basename($path),
            ]);
        }
    }
}
