<?php

namespace App\Listeners;

use App\Enums\ExpenseStatus;
use App\Enums\RewardStatus;
use App\Events\ExpenseRejected;
use App\Events\WorkflowRejected;
use App\Models\Expense;
use App\Models\Reward;

class HandleWorkflowRejected
{
    public function handle(WorkflowRejected $event): void
    {
        $subject = $event->mhw->workflowable;

        match (true) {
            $subject instanceof Expense => $this->handleExpense($subject),
            $subject instanceof Reward => $this->handleReward($subject),
            default => null,
        };
    }

    private function handleExpense(Expense $expense): void
    {
        $expense->update([
            'status' => ExpenseStatus::Rejected,
            'rejected_at' => now(),
        ]);
        event(new ExpenseRejected($expense));
    }

    private function handleReward(Reward $reward): void
    {
        $reward->update([
            'status' => RewardStatus::Rejected,
            'rejected_at' => now(),
        ]);
    }
}
