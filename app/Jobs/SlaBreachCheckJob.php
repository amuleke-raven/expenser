<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Notifications\Tickets\SlaBreachWarningNotification;
use App\Services\TicketActivityLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Permission\Models\Role;

class SlaBreachCheckJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $breachingTickets = Ticket::query()
            ->whereNotNull('due_at')
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->where('due_at', '<', now()->addHours(2))
            ->with(['assignee', 'requester'])
            ->get();

        $adminRole = Role::findByName('admin');
        $admins = $adminRole?->users ?? collect();

        foreach ($breachingTickets as $ticket) {
            if ($ticket->due_at->isPast() && ! $ticket->sla_breached) {
                $ticket->update(['sla_breached' => true]);
                app()->make(TicketActivityLogger::class)->log($ticket, 'sla_breached', [], null);
            }

            $notifiables = collect();

            if ($ticket->assignee) {
                $notifiables->push($ticket->assignee);
            }

            $notifiables = $notifiables->merge($admins)->unique('id');

            foreach ($notifiables as $user) {
                $user->notify(new SlaBreachWarningNotification($ticket));
            }
        }
    }
}
