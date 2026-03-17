<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\RuleKey;
use App\Enums\WorkflowStepStatus;
use App\Exceptions\ExpenseRuleViolationException;
use App\Models\Expense;
use App\Models\Rule;
use App\Models\Workflow;
use Illuminate\Support\Facades\DB;

class ExpenseSubmissionService
{
    public function submit(Expense $expense): void
    {
        if (! $expense->isDraft()) {
            throw new \LogicException('Only draft expenses can be submitted.');
        }

        $this->validateRules($expense);

        DB::transaction(function () use ($expense): void {
            $workflow = Workflow::getForRole($expense->user->role);

            $expense->workflow_id = $workflow->id;
            $expense->status = ExpenseStatus::Submitted;
            $expense->save();

            foreach ($workflow->steps as $step) {
                $expense->workflowSteps()->create([
                    'workflow_step_id' => $step->id,
                    'status' => WorkflowStepStatus::Pending,
                    'step_order' => $step->order,
                ]);
            }
        });
    }

    private function validateRules(Expense $expense): void
    {
        $maxAmount = Rule::getDecimalValue(RuleKey::MaxExpenseAmount);

        if ($maxAmount !== null && $expense->amount > $maxAmount) {
            throw new ExpenseRuleViolationException(
                "Expense amount exceeds the maximum allowed amount of {$maxAmount}."
            );
        }

        $maxAgeDays = Rule::getIntValue(RuleKey::MaxExpenseAge);

        if ($maxAgeDays !== null) {
            $age = $expense->expense_date->diffInDays(now());

            if ($age > $maxAgeDays) {
                throw new ExpenseRuleViolationException(
                    "Expense date is too old. Maximum allowed age is {$maxAgeDays} days."
                );
            }
        }
    }
}
