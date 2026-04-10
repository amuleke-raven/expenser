<?php

namespace App\Notifications\Tickets;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Ticket $ticket) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket assigned to you [{$this->ticket->ticket_number}]")
            ->line("You have been assigned ticket: {$this->ticket->title}")
            ->line("Priority: {$this->ticket->priority->label()}")
            ->line('Due: '.($this->ticket->due_at?->format('Y-m-d H:i') ?? 'Not set'))
            ->action('View Ticket', url("/it/tickets/{$this->ticket->id}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $this->ticket->title,
            'type' => 'ticket_assigned',
        ];
    }
}
