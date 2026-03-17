<?php

namespace Tests\Feature\Staff;

use App\Enums\UserRole;
use App\Enums\WorkflowStepStatus;
use App\Filament\Staff\Pages\ApprovalQueue;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseWorkflowStep;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        filament()->setCurrentPanel(filament()->getPanel('staff'));
    }

    private function createPendingStepForRole(UserRole $role): ExpenseWorkflowStep
    {
        $workflow = Workflow::factory()->create();
        $workflowStep = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'order' => 1,
            'role' => $role->value,
        ]);

        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $currency = Currency::factory()->base()->create();
        $category = Category::factory()->create();

        $expense = Expense::factory()->submitted()->create([
            'user_id' => $staff->id,
            'workflow_id' => $workflow->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
        ]);

        return ExpenseWorkflowStep::factory()->create([
            'expense_id' => $expense->id,
            'workflow_step_id' => $workflowStep->id,
            'status' => WorkflowStepStatus::Pending,
            'step_order' => 1,
        ]);
    }

    public function test_manager_sees_manager_steps_in_queue(): void
    {
        $manager = User::factory()->manager()->create();
        $step = $this->createPendingStepForRole(UserRole::Manager);

        $this->actingAs($manager);

        Livewire::test(ApprovalQueue::class)
            ->assertCanSeeTableRecords(collect([$step]));
    }

    public function test_staff_does_not_see_approval_queue_in_nav(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $this->actingAs($staff);

        $this->assertFalse(ApprovalQueue::shouldRegisterNavigation());
    }

    public function test_finance_does_not_see_manager_steps(): void
    {
        $finance = User::factory()->finance()->create();
        $managerStep = $this->createPendingStepForRole(UserRole::Manager);

        $this->actingAs($finance);

        Livewire::test(ApprovalQueue::class)
            ->assertCanNotSeeTableRecords(collect([$managerStep]));
    }

    public function test_manager_does_not_see_finance_steps(): void
    {
        $manager = User::factory()->manager()->create();
        $financeStep = $this->createPendingStepForRole(UserRole::Finance);

        $this->actingAs($manager);

        Livewire::test(ApprovalQueue::class)
            ->assertCanNotSeeTableRecords(collect([$financeStep]));
    }
}
