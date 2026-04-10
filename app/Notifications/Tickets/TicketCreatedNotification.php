<?php

namespace App\Notifications\Tickets;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
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
            ->subject("New ticket [{$this->ticket->ticket_number}]: {$this->ticket->title}")
            ->line("A new support ticket has been submitted by {$this->ticket->requester->name}.")
            ->line("Category: {$this->ticket->category->name}")
            ->line("Priority: {$this->ticket->priority->label()}")
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
            'type' => 'ticket_created',
        ];
    }
}
