<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseSubmittedNotification extends Notification
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
            ->subject("New expense awaiting review: {$this->expense->ref()}")
            ->line("{$this->expense->user->name} submitted {$this->expense->expenseType->name}")
            ->line("Total: {$this->expense->currency->symbol}{$this->expense->total_amount}")
            ->action('Review Expense', url("/admin/expenses/{$this->expense->id}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'expense_id' => $this->expense->id,
            'ref' => $this->expense->ref(),
            'type' => 'expense_submitted',
        ];
    }
}
