<?php

namespace App\Listeners;

use App\Enums\ExpenseStatus;
use App\Enums\RewardStatus;
use App\Events\ExpenseApproved;
use App\Events\RewardApproved;
use App\Events\WorkflowCompleted;
use App\Models\Expense;
use App\Models\Reward;

class HandleWorkflowCompleted
{
    public function handle(WorkflowCompleted $event): void
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
            'status' => ExpenseStatus::Approved,
            'approved_at' => now(),
        ]);
        event(new ExpenseApproved($expense));
    }

    private function handleReward(Reward $reward): void
    {
        $reward->update([
            'status' => RewardStatus::Approved,
            'approved_at' => now(),
        ]);
        event(new RewardApproved($reward));
    }
}
