<?php

namespace App\Listeners;

use App\Enums\ExpenseStatus;
use App\Events\ExpenseApproved;
use App\Models\User;
use App\Notifications\ExpenseSubmittedNotification;
use App\Services\PaymentPostingService;
use Illuminate\Support\Facades\Notification;

class NotifyAccountingOnExpenseApproval
{
    public function handle(ExpenseApproved $event): void
    {
        $expense = $event->expense;

        // Idempotent guard
        if ($expense->status === ExpenseStatus::Paid) {
            return;
        }

        if ($expense->status !== ExpenseStatus::Approved) {
            $expense->update([
                'status' => ExpenseStatus::Approved,
                'approved_at' => now(),
            ]);
        }

        app(PaymentPostingService::class)->postExpense($expense);

        $accountants = User::role('accountant')->get();
        Notification::send($accountants, new ExpenseSubmittedNotification($expense));
    }
}
