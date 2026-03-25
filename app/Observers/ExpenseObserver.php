<?php

namespace App\Observers;

use App\Enums\ExpenseStatus;
use App\Events\ExpenseSubmitted;
use App\Models\Expense;

class ExpenseObserver
{
    public function updating(Expense $expense): void
    {
        if (
            $expense->isDirty('status') &&
            $expense->status === ExpenseStatus::Submitted &&
            $expense->getOriginal('status') !== ExpenseStatus::Submitted->value
        ) {
            $expense->submitted_at = now();
        }
    }

    public function updated(Expense $expense): void
    {
        if ($expense->wasChanged('status') && $expense->status === ExpenseStatus::Submitted) {
            event(new ExpenseSubmitted($expense));
        }
    }
}
