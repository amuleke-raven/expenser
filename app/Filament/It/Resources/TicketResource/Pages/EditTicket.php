<?php

namespace App\Filament\It\Resources\TicketResource\Pages;

use App\Filament\It\Resources\TicketResource;
use App\Services\TicketActivityLogger;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function afterSave(): void
    {
        $data = $this->data;

        if (! empty($data['internal_note'])) {
            $comment = $this->record->comments()->create([
                'user_id' => auth()->id(),
                'body' => $data['internal_note'],
                'is_internal' => true,
            ]);

            app()->make(TicketActivityLogger::class)->log($this->record, 'comment_added', [
                'comment_id' => $comment->id,
                'is_internal' => true,
            ]);
        }
    }
}
