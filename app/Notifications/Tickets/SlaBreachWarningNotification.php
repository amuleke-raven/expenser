<?php

namespace App\Notifications\Tickets;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaBreachWarningNotification extends Notification implements ShouldQueue
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
        $hoursRemaining = max(0, now()->diffInHours($this->ticket->due_at, false));

        return (new MailMessage)
            ->subject("⚠️ SLA Breach Warning: [{$this->ticket->ticket_number}]")
            ->line("URGENT: Ticket [{$this->ticket->ticket_number}] is approaching its SLA deadline.")
            ->line("Title: {$this->ticket->title}")
            ->line("Hours remaining: {$hoursRemaining}")
            ->line('Due at: '.$this->ticket->due_at?->format('Y-m-d H:i'))
            ->action('View Ticket Now', url("/it/tickets/{$this->ticket->id}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'due_at' => $this->ticket->due_at?->toIsoString(),
            'type' => 'sla_breach_warning',
        ];
    }
}
