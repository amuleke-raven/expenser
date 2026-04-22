<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivityLog;
use App\Models\User;

class TicketActivityLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(Ticket $ticket, string $event, array $meta = [], ?User $user = null): void
    {
        $actor = $user ?? auth()->user();
        $userId = $actor?->id;

        if (
            $event === 'comment_added'
            && $ticket->first_response_at === null
            && $actor !== null
            && $actor->id !== $ticket->requester_id
            && $actor->hasAnyRole(['it_staff', 'admin', 'super_admin'])
        ) {
            $ticket->updateQuietly(['first_response_at' => now()]);
        }

        TicketActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'event' => $event,
            'meta' => ! empty($meta) ? $meta : null,
        ]);
    }
}
