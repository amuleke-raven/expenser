<?php

namespace Database\Factories;

use App\Enums\WorkflowStepStatus;
use App\Models\Expense;
use App\Models\ExpenseWorkflowStep;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseWorkflowStep>
 */
class ExpenseWorkflowStepFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            'workflow_step_id' => WorkflowStep::factory(),
            'actioned_by_user_id' => null,
            'status' => WorkflowStepStatus::Pending,
            'notes' => null,
            'actioned_at' => null,
            'step_order' => 1,
        ];
    }
}
