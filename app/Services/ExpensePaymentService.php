<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;

class ExpensePaymentService
{
    public function generateReport(Expense $expense, User $financeUser): void
    {
        if (! $expense->isApproved()) {
            throw new \LogicException('Only approved expenses can have reports generated.');
        }

        $expense->update(['status' => ExpenseStatus::Processing]);

        $expense->payment()->updateOrCreate(
            ['expense_id' => $expense->id],
            [
                'processed_by_user_id' => $financeUser->id,
                'report_generated_at' => now(),
            ]
        );
    }

    public function confirmPayment(
        Expense $expense,
        string $reference,
        ?int $paymentMethodId = null,
        ?string $notes = null
    ): void {
        if (! $expense->isProcessing()) {
            throw new \LogicException('Only processing expenses can be marked as paid.');
        }

        $expense->update(['status' => ExpenseStatus::Paid]);

        $expense->payment()->update([
            'reference' => $reference,
            'payment_method_id' => $paymentMethodId,
            'paid_at' => now(),
            'notes' => $notes,
        ]);
    }
}
