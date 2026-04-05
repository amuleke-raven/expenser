<?php

namespace App\Listeners;

use App\Enums\ExpenseStatus;
use App\Events\ExpenseApproved;
use App\Events\ExpenseSubmitted;
use App\Models\ModelHasWorkflow;
use App\Services\WorkflowEngine;

class TriggerExpenseWorkflow
{
    public function handle(ExpenseSubmitted $event): void
    {
        $expense = $event->expense->load('expenseType.workflow');

        if ($expense->expenseType->requires_approval && $expense->expenseType->workflow_id) {
            $alreadyInitiated = ModelHasWorkflow::where('workflowable_id', $expense->id)
                ->where('workflowable_type', $expense->getMorphClass())
                ->exists();

            if ($alreadyInitiated) {
                return;
            }

            $expense->updateQuietly(['status' => ExpenseStatus::UnderReview]);
            app(WorkflowEngine::class)->initiate($expense, $expense->expenseType->workflow);
        } else {
            event(new ExpenseApproved($expense));
        }
    }
}
