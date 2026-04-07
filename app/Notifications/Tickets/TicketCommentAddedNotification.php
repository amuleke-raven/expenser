<?php

namespace App\Notifications\Tickets;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCommentAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketComment $comment,
    ) {}

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
            ->subject("New reply on ticket [{$this->ticket->ticket_number}]")
            ->line("IT Support has replied to your ticket: {$this->ticket->title}")
            ->line(strip_tags($this->comment->body))
            ->action('View Ticket', url("/portal/tickets/{$this->ticket->id}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'comment_id' => $this->comment->id,
            'type' => 'ticket_comment_added',
        ];
    }
}
