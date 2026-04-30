<?php

namespace App\Filament\It\Pages;

use App\Enums\TicketStatus;
use App\Filament\It\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketActivityLogger;
use Filament\Pages\Page;

class TicketKanbanPage extends Page
{
    protected string $view = 'filament.it.pages.ticket-kanban-page';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static string|\UnitEnum|null $navigationGroup = 'IT Support';

    protected static ?string $navigationLabel = 'Kanban Board';

    protected static ?string $title = 'Ticket Kanban Board';

    protected static ?string $slug = 'tickets/kanban';

    /** @var array<string, list<array<string, mixed>>> */
    public array $columns = [];

    public function mount(): void
    {
        $this->loadTickets();
    }

    private function loadTickets(): void
    {
        $statuses = [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::OnHold->value,
            TicketStatus::Escalated->value,
        ];

        $this->columns = array_fill_keys($statuses, []);

        $tickets = Ticket::query()
            ->whereIn('status', $statuses)
            ->with(['requester', 'category'])
            ->orderBy('due_at')
            ->get();

        foreach ($tickets as $ticket) {
            $countdown = $ticket->sla_countdown_hours;

            $slaColor = match (true) {
                $ticket->sla_breached || $countdown < 1 => 'bg-red-100 text-red-700',
                $countdown < 4 => 'bg-amber-100 text-amber-700',
                default => 'bg-green-100 text-green-700',
            };

            $priorityColorMap = [
                'low' => '#6b7280',
                'medium' => '#3b82f6',
                'high' => '#f59e0b',
                'critical' => '#ef4444',
            ];

            $requesterName = $ticket->requester->name ?? '';
            $initials = implode('', array_map(
                fn ($word) => strtoupper(substr($word, 0, 1)),
                array_slice(explode(' ', $requesterName), 0, 2)
            ));

            $statusValue = $ticket->status instanceof TicketStatus
                ? $ticket->status->value
                : $ticket->status;

            $this->columns[$statusValue][] = [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'priority_label' => $ticket->priority->label(),
                'priority_color_css' => $priorityColorMap[$ticket->priority->value] ?? '#6b7280',
                'requester_initials' => $initials ?: '?',
                'sla_countdown_hours' => $countdown,
                'sla_color' => $slaColor,
                'edit_url' => TicketResource::getUrl('edit', ['record' => $ticket->id]),
            ];
        }
    }

    /** @return array<string, string> */
    public function getColumnLabels(): array
    {
        return [
            TicketStatus::Open->value => 'Open',
            TicketStatus::InProgress->value => 'In Progress',
            TicketStatus::OnHold->value => 'On Hold',
            TicketStatus::Escalated->value => 'Escalated',
        ];
    }

    public function updateTicketStatus(int $ticketId, string $newStatus): void
    {
        $ticket = Ticket::findOrFail($ticketId);

        $this->authorize('update', $ticket);

        $validStatus = TicketStatus::tryFrom($newStatus);
        if ($validStatus === null) {
            return;
        }

        $allowedTransitions = $ticket->status->allowedTransitionsFor('it_staff');
        if (! in_array($validStatus, $allowedTransitions, true)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "Cannot move ticket to {$validStatus->label()} from {$ticket->status->label()}.",
            ]);

            return;
        }

        $oldStatus = $ticket->status;
        $ticket->update(['status' => $validStatus]);

        app()->make(TicketActivityLogger::class)->log($ticket, 'status_changed', [
            'from' => $oldStatus->value,
            'to' => $validStatus->value,
        ]);

        $this->loadTickets();
    }

    protected function getViewData(): array
    {
        return [
            'columns' => $this->columns,
            'columnLabels' => $this->getColumnLabels(),
        ];
    }
}
