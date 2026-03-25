<?php

namespace App\Listeners;

use App\Enums\ExpenseStatus;
use App\Events\ExpenseApproved;
use App\Events\ExpenseSubmitted;
use App\Services\WorkflowEngine;

class TriggerExpenseWorkflow
{
    public function handle(ExpenseSubmitted $event): void
    {
        $expense = $event->expense->load('expenseType.workflow');

        if ($expense->expenseType->requires_approval && $expense->expenseType->workflow_id) {
            $expense->updateQuietly(['status' => ExpenseStatus::UnderReview]);
            app(WorkflowEngine::class)->initiate($expense, $expense->expenseType->workflow);
        } else {
            event(new ExpenseApproved($expense));
        }
    }
}
