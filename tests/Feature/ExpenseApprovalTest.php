<?php

namespace Tests\Feature;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Enums\WorkflowStepStatus;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseWorkflowStep;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\ExpenseApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseApprovalTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExpenseApprovalService::class);
    }

    private function createSubmittedExpenseWithSteps(): array
    {
        $workflow = Workflow::factory()->default()->create();
        $managerStep = WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'order' => 1, 'role' => UserRole::Manager->value]);
        $financeStep = WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'order' => 2, 'role' => UserRole::Finance->value]);

        $user = User::factory()->create(['role' => UserRole::Staff]);
        $currency = Currency::factory()->base()->create();
        $category = Category::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'workflow_id' => $workflow->id,
            'status' => ExpenseStatus::Submitted,
        ]);

        $step1 = ExpenseWorkflowStep::factory()->create([
            'expense_id' => $expense->id,
            'workflow_step_id' => $managerStep->id,
            'status' => WorkflowStepStatus::Pending,
            'step_order' => 1,
        ]);
        $step2 = ExpenseWorkflowStep::factory()->create([
            'expense_id' => $expense->id,
            'workflow_step_id' => $financeStep->id,
            'status' => WorkflowStepStatus::Pending,
            'step_order' => 2,
        ]);

        return [$expense, $step1, $step2];
    }

    public function test_manager_can_approve_their_step(): void
    {
        [$expense, $step1] = $this->createSubmittedExpenseWithSteps();
        $manager = User::factory()->manager()->create();

        $this->service->approve($step1, $manager, 'Looks good');

        $this->assertEquals(WorkflowStepStatus::Approved, $step1->fresh()->status);
        $this->assertEquals($manager->id, $step1->fresh()->actioned_by_user_id);
    }

    public function test_wrong_role_cannot_approve_step(): void
    {
        [$expense, $step1] = $this->createSubmittedExpenseWithSteps();
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $this->expectException(\LogicException::class);
        $this->service->approve($step1, $staff);
    }

    public function test_all_steps_approved_marks_expense_approved(): void
    {
        [$expense, $step1, $step2] = $this->createSubmittedExpenseWithSteps();
        $manager = User::factory()->manager()->create();
        $finance = User::factory()->finance()->create();

        $this->service->approve($step1, $manager);
        $this->service->approve($step2, $finance);

        $this->assertEquals(ExpenseStatus::Approved, $expense->fresh()->status);
    }

    public function test_approval_creates_payment_record_when_fully_approved(): void
    {
        [$expense, $step1, $step2] = $this->createSubmittedExpenseWithSteps();
        $manager = User::factory()->manager()->create();
        $finance = User::factory()->finance()->create();

        $this->service->approve($step1, $manager);
        $this->service->approve($step2, $finance);

        $this->assertDatabaseHas('expense_payments', ['expense_id' => $expense->id]);
    }

    public function test_reject_marks_expense_rejected(): void
    {
        [$expense, $step1] = $this->createSubmittedExpenseWithSteps();
        $manager = User::factory()->manager()->create();

        $this->service->reject($step1, $manager, 'policy_violation', 'Amount too high');

        $this->assertEquals(ExpenseStatus::Rejected, $expense->fresh()->status);
        $this->assertEquals('policy_violation', $expense->fresh()->rejection_reason);
    }

    public function test_reject_skips_subsequent_pending_steps(): void
    {
        [$expense, $step1, $step2] = $this->createSubmittedExpenseWithSteps();
        $manager = User::factory()->manager()->create();

        $this->service->reject($step1, $manager, 'policy_violation');

        $this->assertEquals(WorkflowStepStatus::Skipped, $step2->fresh()->status);
    }

    public function test_cannot_approve_non_pending_step(): void
    {
        [$expense, $step1] = $this->createSubmittedExpenseWithSteps();
        $manager = User::factory()->manager()->create();

        $step1->update(['status' => WorkflowStepStatus::Approved]);

        $this->expectException(\LogicException::class);
        $this->service->approve($step1->fresh(), $manager);
    }
}
