<?php

namespace App\Listeners;

use App\Enums\ExpenseStatus;
use App\Enums\RewardStatus;
use App\Enums\StepActionStatus;
use App\Events\ExpenseRejected;
use App\Events\WorkflowRejected;
use App\Models\Expense;
use App\Models\Reward;

class HandleWorkflowRejected
{
    public function handle(WorkflowRejected $event): void
    {
        $subject = $event->mhw->workflowable;

        $rejectionReason = $event->mhw->stepActions()
            ->where('status', StepActionStatus::Rejected)
            ->latest('actioned_at')
            ->value('notes');

        match (true) {
            $subject instanceof Expense => $this->handleExpense($subject, $rejectionReason),
            $subject instanceof Reward => $this->handleReward($subject),
            default => null,
        };
    }

    private function handleExpense(Expense $expense, ?string $rejectionReason): void
    {
        $expense->update([
            'status' => ExpenseStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => $rejectionReason,
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
