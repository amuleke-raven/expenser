<?php

namespace Tests\Feature\Staff;

use App\Enums\ExpenseStatus;
use App\Enums\StepActionStatus;
use App\Enums\StepActionType;
use App\Enums\WorkflowStatus;
use App\Filament\Staff\Resources\ExpenseResource\Pages\ListExpenses;
use App\Models\Expense;
use App\Models\ModelHasWorkflow;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseCancelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('staff'));
    }

    public function test_submitted_expense_can_be_cancelled(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Submitted,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->callTableAction('cancel', $expense);

        $this->assertDatabaseHas(Expense::class, [
            'id' => $expense->id,
            'status' => ExpenseStatus::Cancelled->value,
        ]);
    }

    public function test_under_review_expense_can_be_cancelled(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::UnderReview,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->callTableAction('cancel', $expense);

        $this->assertDatabaseHas(Expense::class, [
            'id' => $expense->id,
            'status' => ExpenseStatus::Cancelled->value,
        ]);
    }

    public function test_rejected_expense_can_be_cancelled(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Rejected,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->callTableAction('cancel', $expense);

        $this->assertDatabaseHas(Expense::class, [
            'id' => $expense->id,
            'status' => ExpenseStatus::Cancelled->value,
        ]);
    }

    public function test_cancelling_closes_the_active_workflow(): void
    {
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        $workflow = Workflow::create(['name' => 'Test Workflow']);
        $step = WorkflowStep::create([
            'workflow_id' => $workflow->id,
            'name' => 'Manager Review',
            'order' => 1,
            'action_type' => StepActionType::Approval,
            'role_id' => $managerRole->id,
        ]);

        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::UnderReview,
        ]);

        $mhw = ModelHasWorkflow::create([
            'workflow_id' => $workflow->id,
            'workflowable_id' => $expense->id,
            'workflowable_type' => Expense::class,
            'current_step' => 1,
            'status' => WorkflowStatus::InProgress,
            'started_at' => now(),
        ]);

        $action = WorkflowStepAction::create([
            'model_has_workflow_id' => $mhw->id,
            'workflow_step_id' => $step->id,
            'status' => StepActionStatus::Pending,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->callTableAction('cancel', $expense);

        $this->assertDatabaseHas(Expense::class, [
            'id' => $expense->id,
            'status' => ExpenseStatus::Cancelled->value,
        ]);

        $this->assertDatabaseHas(ModelHasWorkflow::class, [
            'id' => $mhw->id,
            'status' => WorkflowStatus::Cancelled->value,
        ]);

        $this->assertDatabaseHas(WorkflowStepAction::class, [
            'id' => $action->id,
            'status' => StepActionStatus::Cancelled->value,
        ]);
    }

    public function test_draft_expense_cannot_be_cancelled(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Draft,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->assertTableActionHidden('cancel', $expense);
    }

    public function test_approved_expense_cannot_be_cancelled(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Approved,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->assertTableActionHidden('cancel', $expense);
    }

    public function test_paid_expense_cannot_be_cancelled(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Paid,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->assertTableActionHidden('cancel', $expense);
    }
}
