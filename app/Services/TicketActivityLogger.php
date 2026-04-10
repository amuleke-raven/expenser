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
        $userId = $user !== null ? $user->id : auth()->id();

        TicketActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'event' => $event,
            'meta' => ! empty($meta) ? $meta : null,
        ]);
    }
}
