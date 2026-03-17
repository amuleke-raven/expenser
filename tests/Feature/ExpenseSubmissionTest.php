<?php

namespace Tests\Feature;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Enums\WorkflowStepStatus;
use App\Exceptions\ExpenseRuleViolationException;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Rule;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\ExpenseSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseSubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExpenseSubmissionService::class);
    }

    private function createDefaultWorkflow(): Workflow
    {
        $workflow = Workflow::factory()->default()->create();
        WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'order' => 1, 'name' => 'Manager Review', 'role' => UserRole::Manager->value]);
        WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'order' => 2, 'name' => 'Finance Approval', 'role' => UserRole::Finance->value]);

        return $workflow;
    }

    private function createDraftExpense(array $overrides = []): Expense
    {
        $user = User::factory()->create(['role' => UserRole::Staff]);
        $currency = Currency::factory()->base()->create();
        $category = Category::factory()->create();

        return Expense::factory()->create(array_merge([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'amount' => 500,
            'expense_date' => now()->subDays(5),
            'status' => ExpenseStatus::Draft,
        ], $overrides));
    }

    public function test_can_create_draft_expense(): void
    {
        $expense = $this->createDraftExpense();

        $this->assertEquals(ExpenseStatus::Draft, $expense->status);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'status' => 'draft']);
    }

    public function test_submitting_expense_creates_workflow_steps(): void
    {
        $this->createDefaultWorkflow();
        $expense = $this->createDraftExpense();

        $this->service->submit($expense);

        $this->assertEquals(ExpenseStatus::Submitted, $expense->fresh()->status);
        $this->assertDatabaseCount('expense_workflow_steps', 2);
    }

    public function test_submitted_expense_has_pending_steps(): void
    {
        $this->createDefaultWorkflow();
        $expense = $this->createDraftExpense();

        $this->service->submit($expense);

        $expense->refresh();
        $this->assertTrue($expense->workflowSteps->every(fn ($step) => $step->status === WorkflowStepStatus::Pending));
    }

    public function test_submission_assigns_workflow_to_expense(): void
    {
        $workflow = $this->createDefaultWorkflow();
        $expense = $this->createDraftExpense();

        $this->service->submit($expense);

        $this->assertEquals($workflow->id, $expense->fresh()->workflow_id);
    }

    public function test_submission_fails_when_amount_exceeds_max(): void
    {
        $this->createDefaultWorkflow();
        Rule::factory()->create(['key' => 'max_expense_amount', 'value' => '100']);
        $expense = $this->createDraftExpense(['amount' => 500]);

        $this->expectException(ExpenseRuleViolationException::class);
        $this->service->submit($expense);
    }

    public function test_submission_fails_when_expense_too_old(): void
    {
        $this->createDefaultWorkflow();
        Rule::factory()->create(['key' => 'max_expense_age', 'value' => '30']);
        $expense = $this->createDraftExpense(['expense_date' => now()->subDays(60)]);

        $this->expectException(ExpenseRuleViolationException::class);
        $this->service->submit($expense);
    }

    public function test_submission_fails_for_non_draft_expense(): void
    {
        $this->createDefaultWorkflow();
        $expense = $this->createDraftExpense(['status' => ExpenseStatus::Submitted]);

        $this->expectException(\LogicException::class);
        $this->service->submit($expense);
    }

    public function test_workflow_assigned_by_role_mapping(): void
    {
        $defaultWorkflow = $this->createDefaultWorkflow();
        $managerWorkflow = Workflow::factory()->create(['is_default' => false]);
        WorkflowStep::factory()->create(['workflow_id' => $managerWorkflow->id, 'order' => 1, 'name' => 'Direct Finance', 'role' => UserRole::Finance->value]);
        $managerWorkflow->roleWorkflows()->create(['role' => UserRole::Manager->value]);

        $manager = User::factory()->manager()->create();
        $currency = Currency::factory()->base()->create();
        $category = Category::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $manager->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'amount' => 100,
            'expense_date' => now()->subDays(5),
            'status' => ExpenseStatus::Draft,
        ]);

        $this->service->submit($expense);

        $this->assertEquals($managerWorkflow->id, $expense->fresh()->workflow_id);
    }
}
