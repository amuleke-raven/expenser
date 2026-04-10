<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole(['admin', 'super_admin', 'it_staff'])) {
            return true;
        }

        return $ticket->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('staff');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin', 'it_staff']);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function addComment(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin', 'it_staff'])
            || $ticket->requester_id === $user->id
            || $ticket->assignee_id === $user->id;
    }

    public function viewInternalNotes(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin', 'it_staff']);
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $ticket->requester_id === $user->id
            && $ticket->status === TicketStatus::Resolved
            && $ticket->resolved_at !== null
            && $ticket->resolved_at->gt(now()->subHours(48));
    }

    public function cancel(User $user, Ticket $ticket): bool
    {
        return $ticket->requester_id === $user->id
            && in_array($ticket->status, [TicketStatus::Draft, TicketStatus::Open]);
    }

    public function forceClose(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function reassign(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
