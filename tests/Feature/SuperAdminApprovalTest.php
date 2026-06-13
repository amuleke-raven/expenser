<?php

namespace Tests\Feature;

use App\Enums\ExpenseStatus;
use App\Enums\StepActionStatus;
use App\Enums\StepActionType;
use App\Enums\WorkflowStatus;
use App\Models\Expense;
use App\Models\ModelHasWorkflow;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepAction;
use App\Services\WorkflowEngine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Workflow $workflow;

    private WorkflowStep $stepOne;

    private WorkflowStep $stepTwo;

    private WorkflowStep $stepThree;

    protected function setUp(): void
    {
        parent::setUp();

        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->workflow = Workflow::create(['name' => 'Test Workflow']);

        $this->stepOne = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Manager Review',
            'order' => 1,
            'action_type' => StepActionType::Approval,
            'role_id' => $managerRole->id,
        ]);

        $this->stepTwo = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Admin Review',
            'order' => 2,
            'action_type' => StepActionType::Approval,
            'role_id' => $adminRole->id,
        ]);

        $this->stepThree = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Final Review',
            'order' => 3,
            'action_type' => StepActionType::Approval,
            'role_id' => $adminRole->id,
        ]);
    }

    private function createPendingExpenseWorkflow(): array
    {
        $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);

        $mhw = ModelHasWorkflow::create([
            'workflow_id' => $this->workflow->id,
            'workflowable_id' => $expense->id,
            'workflowable_type' => Expense::class,
            'current_step' => 1,
            'status' => WorkflowStatus::InProgress,
            'started_at' => now(),
        ]);

        $action = WorkflowStepAction::create([
            'model_has_workflow_id' => $mhw->id,
            'workflow_step_id' => $this->stepOne->id,
            'status' => StepActionStatus::Pending,
        ]);

        return [$expense, $mhw, $action];
    }

    public function test_super_approve_marks_current_action_as_approved(): void
    {
        [, , $action] = $this->createPendingExpenseWorkflow();

        app(WorkflowEngine::class)->superApprove($action, 'Approved by admin', $this->superAdmin);

        $this->assertDatabaseHas(WorkflowStepAction::class, [
            'id' => $action->id,
            'status' => StepActionStatus::Approved->value,
            'actor_id' => $this->superAdmin->id,
            'notes' => 'Approved by admin',
        ]);
    }

    public function test_super_approve_skips_all_remaining_steps(): void
    {
        [, $mhw, $action] = $this->createPendingExpenseWorkflow();

        app(WorkflowEngine::class)->superApprove($action, null, $this->superAdmin);

        $this->assertDatabaseHas(WorkflowStepAction::class, [
            'model_has_workflow_id' => $mhw->id,
            'workflow_step_id' => $this->stepTwo->id,
            'status' => StepActionStatus::Skipped->value,
            'actor_id' => $this->superAdmin->id,
        ]);

        $this->assertDatabaseHas(WorkflowStepAction::class, [
            'model_has_workflow_id' => $mhw->id,
            'workflow_step_id' => $this->stepThree->id,
            'status' => StepActionStatus::Skipped->value,
            'actor_id' => $this->superAdmin->id,
        ]);
    }

    public function test_super_approve_completes_the_workflow(): void
    {
        [, $mhw, $action] = $this->createPendingExpenseWorkflow();

        app(WorkflowEngine::class)->superApprove($action, null, $this->superAdmin);

        $this->assertDatabaseHas(ModelHasWorkflow::class, [
            'id' => $mhw->id,
            'status' => WorkflowStatus::Completed->value,
        ]);

        $this->assertNotNull($mhw->fresh()->completed_at);
    }

    public function test_super_approve_marks_expense_as_approved(): void
    {
        [$expense, , $action] = $this->createPendingExpenseWorkflow();

        app(WorkflowEngine::class)->superApprove($action, null, $this->superAdmin);

        $this->assertDatabaseHas(Expense::class, [
            'id' => $expense->id,
            'status' => ExpenseStatus::Approved->value,
        ]);

        $this->assertNotNull($expense->fresh()->approved_at);
    }

    public function test_super_approve_on_last_step_still_completes_workflow(): void
    {
        $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);

        $mhw = ModelHasWorkflow::create([
            'workflow_id' => $this->workflow->id,
            'workflowable_id' => $expense->id,
            'workflowable_type' => Expense::class,
            'current_step' => 3,
            'status' => WorkflowStatus::InProgress,
            'started_at' => now(),
        ]);

        // Steps 1 and 2 were already approved
        WorkflowStepAction::create([
            'model_has_workflow_id' => $mhw->id,
            'workflow_step_id' => $this->stepOne->id,
            'status' => StepActionStatus::Approved,
            'actioned_at' => now(),
        ]);
        WorkflowStepAction::create([
            'model_has_workflow_id' => $mhw->id,
            'workflow_step_id' => $this->stepTwo->id,
            'status' => StepActionStatus::Approved,
            'actioned_at' => now(),
        ]);

        $finalAction = WorkflowStepAction::create([
            'model_has_workflow_id' => $mhw->id,
            'workflow_step_id' => $this->stepThree->id,
            'status' => StepActionStatus::Pending,
        ]);

        app(WorkflowEngine::class)->superApprove($finalAction, null, $this->superAdmin);

        $this->assertDatabaseHas(ModelHasWorkflow::class, [
            'id' => $mhw->id,
            'status' => WorkflowStatus::Completed->value,
        ]);

        $this->assertDatabaseHas(Expense::class, [
            'id' => $expense->id,
            'status' => ExpenseStatus::Approved->value,
        ]);
    }

    public function test_user_cannot_approve_their_own_submitted_expense(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $expense = Expense::factory()->create([
            'user_id' => $manager->id,
            'status' => ExpenseStatus::Submitted,
        ]);

        $mhw = ModelHasWorkflow::create([
            'workflow_id' => $this->workflow->id,
            'workflowable_id' => $expense->id,
            'workflowable_type' => Expense::class,
            'current_step' => 1,
            'status' => WorkflowStatus::InProgress,
            'started_at' => now(),
        ]);

        $action = WorkflowStepAction::create([
            'model_has_workflow_id' => $mhw->id,
            'workflow_step_id' => $this->stepOne->id,
            'status' => StepActionStatus::Pending,
        ]);

        try {
            app(WorkflowEngine::class)->advance($action, StepActionStatus::Approved, null, $manager);
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            // expected
        }

        $this->assertDatabaseHas(WorkflowStepAction::class, [
            'id' => $action->id,
            'status' => StepActionStatus::Pending->value,
        ]);

        $this->assertDatabaseHas(ModelHasWorkflow::class, [
            'id' => $mhw->id,
            'status' => WorkflowStatus::InProgress->value,
        ]);
    }

    public function test_regular_approve_still_advances_to_next_step_for_non_super_admin(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        [, $mhw, $action] = $this->createPendingExpenseWorkflow();

        app(WorkflowEngine::class)->advance($action, StepActionStatus::Approved, null, $manager);

        $this->assertDatabaseHas(ModelHasWorkflow::class, [
            'id' => $mhw->id,
            'current_step' => 2,
            'status' => WorkflowStatus::InProgress->value,
        ]);

        // Workflow should NOT be completed yet
        $this->assertDatabaseMissing(ModelHasWorkflow::class, [
            'id' => $mhw->id,
            'status' => WorkflowStatus::Completed->value,
        ]);
    }
}
