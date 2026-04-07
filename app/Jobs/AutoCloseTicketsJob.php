<?php

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketActivityLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoCloseTicketsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $logger = app()->make(TicketActivityLogger::class);

        Ticket::query()
            ->where('status', TicketStatus::Resolved->value)
            ->where('resolved_at', '<', now()->subHours(48))
            ->each(function (Ticket $ticket) use ($logger) {
                $ticket->update(['status' => TicketStatus::Closed]);
                $logger->log($ticket, 'auto_closed', [], null);
            });
    }
}
