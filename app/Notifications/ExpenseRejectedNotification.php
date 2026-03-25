<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Expense $expense) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your expense {$this->expense->ref()} was not approved")
            ->line("Reason: {$this->expense->rejection_reason}")
            ->action('View Expense', url("/portal/expenses/{$this->expense->id}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'expense_id' => $this->expense->id,
            'ref' => $this->expense->ref(),
            'type' => 'expense_rejected',
        ];
    }
}
