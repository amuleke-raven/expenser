<?php

namespace App\Observers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Notifications\Tickets\TicketAssignedNotification;
use App\Notifications\Tickets\TicketCreatedNotification;
use App\Notifications\Tickets\TicketStatusChangedNotification;
use App\Services\TicketActivityLogger;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        app()->make(TicketActivityLogger::class)->log($ticket, 'ticket_created');

        $itStaffRole = Role::findByName('it_staff');
        $itStaffUsers = $itStaffRole?->users ?? collect();

        Notification::send($itStaffUsers, new TicketCreatedNotification($ticket));
    }

    public function updating(Ticket $ticket): void
    {
        if ($ticket->isDirty('status')) {
            if ($ticket->status === TicketStatus::Resolved) {
                $ticket->resolved_at = now();
            }

            if ($ticket->status === TicketStatus::Open) {
                $ticket->sla_breach_notified = false;
            }
        }

        $actor = auth()->user();
        if (
            $ticket->first_response_at === null
            && $actor !== null
            && $actor->id !== $ticket->requester_id
            && $actor->hasAnyRole(['it_staff', 'admin', 'super_admin'])
        ) {
            $ticket->first_response_at = now();
        }
    }

    public function updated(Ticket $ticket): void
    {
        if ($ticket->wasChanged('status')) {
            $from = TicketStatus::from($ticket->getRawOriginal('status'));
            $to = $ticket->status;

            app()->make(TicketActivityLogger::class)->log($ticket, 'status_changed', [
                'from' => $from->value,
                'to' => $to->value,
            ]);

            if ($to !== TicketStatus::Draft) {
                $ticket->requester->notify(new TicketStatusChangedNotification($ticket, $from, $to));
            }
        }

        if ($ticket->wasChanged('assignee_id') && $ticket->assignee_id !== null) {
            app()->make(TicketActivityLogger::class)->log($ticket, 'assigned', [
                'assignee_id' => $ticket->assignee_id,
                'assignee_name' => $ticket->assignee?->name,
            ]);

            $ticket->assignee?->notify(new TicketAssignedNotification($ticket));
        }
    }
}
