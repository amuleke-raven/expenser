<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseApprovedNotification extends Notification
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
            ->subject("Your expense {$this->expense->ref()} has been approved")
            ->line("Amount: {$this->expense->total_amount} {$this->expense->currency->code}")
            ->line("Approved: {$this->expense->approved_at?->format('d M Y')}");
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'expense_id' => $this->expense->id,
            'ref' => $this->expense->ref(),
            'type' => 'expense_approved',
        ];
    }
}
