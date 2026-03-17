<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\WorkflowStepStatus;
use App\Models\ExpenseWorkflowStep;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseApprovalService
{
    public function approve(ExpenseWorkflowStep $step, User $user, ?string $notes = null): void
    {
        if ($step->workflowStep->role !== $user->role) {
            throw new \LogicException('User role does not match the required role for this step.');
        }

        if (! $step->isPending()) {
            throw new \LogicException('This step is not pending approval.');
        }

        DB::transaction(function () use ($step, $user, $notes): void {
            $step->update([
                'status' => WorkflowStepStatus::Approved,
                'actioned_by_user_id' => $user->id,
                'actioned_at' => now(),
                'notes' => $notes,
            ]);

            $expense = $step->expense;
            $allApproved = $expense->workflowSteps()
                ->whereNotIn('status', [WorkflowStepStatus::Approved->value, WorkflowStepStatus::Skipped->value])
                ->doesntExist();

            if ($allApproved) {
                $expense->update(['status' => ExpenseStatus::Approved]);
                $expense->payment()->create([]);
            }
        });
    }

    public function reject(ExpenseWorkflowStep $step, User $user, string $reason, ?string $comment = null): void
    {
        if ($step->workflowStep->role !== $user->role) {
            throw new \LogicException('User role does not match the required role for this step.');
        }

        if (! $step->isPending()) {
            throw new \LogicException('This step is not pending approval.');
        }

        DB::transaction(function () use ($step, $user, $reason, $comment): void {
            $step->update([
                'status' => WorkflowStepStatus::Rejected,
                'actioned_by_user_id' => $user->id,
                'actioned_at' => now(),
                'notes' => $comment,
            ]);

            $expense = $step->expense;

            $expense->workflowSteps()
                ->where('status', WorkflowStepStatus::Pending->value)
                ->update(['status' => WorkflowStepStatus::Skipped->value]);

            $expense->update([
                'status' => ExpenseStatus::Rejected,
                'rejection_reason' => $reason,
                'rejection_comment' => $comment,
            ]);
        });
    }
}
