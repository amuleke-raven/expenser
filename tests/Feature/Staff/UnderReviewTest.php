<?php

namespace Tests\Feature\Staff;

use App\Enums\ExpenseStatus;
use App\Enums\RecipientType;
use App\Enums\RewardStatus;
use App\Enums\StepActionStatus;
use App\Enums\StepActionType;
use App\Enums\WorkflowStatus;
use App\Filament\Staff\Resources\UnderReviewResource;
use App\Filament\Staff\Resources\UnderReviewResource\Pages\ListUnderReview;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ModelHasWorkflow;
use App\Models\Reward;
use App\Models\RewardType;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnderReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workflow $workflow;

    private WorkflowStep $financeStep;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $financeRole = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);

        $this->workflow = Workflow::create(['name' => 'Expense Approval']);

        WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Manager Review',
            'order' => 1,
            'action_type' => StepActionType::Approval,
            'role_id' => $managerRole->id,
        ]);

        $this->financeStep = WorkflowStep::create([
            'workflow_id' => $this->workflow->id,
            'name' => 'Finance Review',
            'order' => 2,
            'action_type' => StepActionType::Approval,
            'role_id' => $financeRole->id,
        ]);
    }

    private function startWorkflow(Expense|Reward $subject, int $currentStep, WorkflowStatus $status = WorkflowStatus::InProgress): ModelHasWorkflow
    {
        $modelHasWorkflow = ModelHasWorkflow::create([
            'workflow_id' => $this->workflow->id,
            'workflowable_id' => $subject->id,
            'workflowable_type' => $subject->getMorphClass(),
            'current_step' => $currentStep,
            'status' => $status,
            'started_at' => now(),
        ]);

        foreach ($this->workflow->steps()->where('order', '<', $currentStep)->get() as $completedStep) {
            WorkflowStepAction::create([
                'model_has_workflow_id' => $modelHasWorkflow->id,
                'workflow_step_id' => $completedStep->id,
                'actor_id' => $this->user->id,
                'status' => StepActionStatus::Approved,
                'actioned_at' => now(),
            ]);
        }

        WorkflowStepAction::create([
            'model_has_workflow_id' => $modelHasWorkflow->id,
            'workflow_step_id' => $this->workflow->steps()->where('order', $currentStep)->first()->id,
            'status' => StepActionStatus::Pending,
        ]);

        return $modelHasWorkflow;
    }

    private function createExpense(): Expense
    {
        return Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::UnderReview,
        ]);
    }

    private function createReward(): Reward
    {
        $rewardType = RewardType::create([
            'name' => 'Referral Bonus',
            'is_fixed' => false,
            'is_client_based' => false,
            'requires_approval' => true,
            'allows_custom_message' => false,
            'allows_attachments' => false,
            'requires_attachments' => false,
            'is_recurrent' => false,
        ]);

        return Reward::create([
            'reward_type_id' => $rewardType->id,
            'initiated_by' => $this->user->id,
            'amount' => 500,
            'currency_id' => Currency::factory()->create()->id,
            'is_billable' => false,
            'status' => RewardStatus::PendingApproval,
            'recipient_type' => RecipientType::Internal,
        ]);
    }

    public function test_any_staff_member_can_access_the_under_review_list(): void
    {
        $this->actingAs($this->user);

        $this->assertTrue(UnderReviewResource::canAccess());

        Livewire::test(ListUnderReview::class)->assertSuccessful();
    }

    public function test_it_lists_expenses_and_disbursements_awaiting_approval(): void
    {
        $expenseWorkflow = $this->startWorkflow($this->createExpense(), currentStep: 1);
        $rewardWorkflow = $this->startWorkflow($this->createReward(), currentStep: 1);

        $this->actingAs($this->user);

        Livewire::test(ListUnderReview::class)
            ->assertCanSeeTableRecords([$expenseWorkflow, $rewardWorkflow]);
    }

    public function test_it_excludes_workflows_that_are_no_longer_pending_approval(): void
    {
        $pending = $this->startWorkflow($this->createExpense(), currentStep: 1);
        $completed = $this->startWorkflow($this->createExpense(), currentStep: 2, status: WorkflowStatus::Completed);
        $cancelled = $this->startWorkflow($this->createExpense(), currentStep: 1, status: WorkflowStatus::Cancelled);

        $this->actingAs($this->user);

        Livewire::test(ListUnderReview::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$completed, $cancelled]);
    }

    public function test_it_shows_the_pending_approval_level_and_awaiting_role(): void
    {
        $modelHasWorkflow = $this->startWorkflow($this->createExpense(), currentStep: 2);

        $this->actingAs($this->user);

        Livewire::test(ListUnderReview::class)
            ->assertTableColumnStateSet('pending_level', 'Level 2 of 2 — Finance Review', $modelHasWorkflow)
            ->assertTableColumnStateSet('awaiting_role', 'Finance', $modelHasWorkflow);
    }

    public function test_the_approval_trail_records_every_level(): void
    {
        $modelHasWorkflow = $this->startWorkflow($this->createExpense(), currentStep: 2);

        $trail = UnderReviewResource::approvalTrail(
            $modelHasWorkflow->fresh(['workflow.steps.role', 'stepActions.actor'])
        );

        $this->assertSame('Level 1', $trail[0]['level']);
        $this->assertSame('Manager Review', $trail[0]['name']);
        $this->assertSame(StepActionStatus::Approved->value, $trail[0]['status']);
        $this->assertSame($this->user->name, $trail[0]['actor']);

        $this->assertSame('Level 2 (current)', $trail[1]['level']);
        $this->assertSame('Finance', $trail[1]['role']);
        $this->assertSame(StepActionStatus::Pending->value, $trail[1]['status']);
    }

    public function test_the_details_slide_over_can_be_opened(): void
    {
        $expenseWorkflow = $this->startWorkflow($this->createExpense(), currentStep: 2);
        $rewardWorkflow = $this->startWorkflow($this->createReward(), currentStep: 1);

        $this->actingAs($this->user);

        Livewire::test(ListUnderReview::class)
            ->mountAction(TestAction::make('viewDetails')->table($expenseWorkflow))
            ->assertSuccessful()
            ->assertSee('Finance Review')
            ->unmountAction()
            ->mountAction(TestAction::make('viewDetails')->table($rewardWorkflow))
            ->assertSuccessful()
            ->assertSee('Manager Review');
    }

    public function test_it_can_be_filtered_by_the_role_the_approval_is_awaiting(): void
    {
        $atManager = $this->startWorkflow($this->createExpense(), currentStep: 1);
        $atFinance = $this->startWorkflow($this->createExpense(), currentStep: 2);

        $this->actingAs($this->user);

        Livewire::test(ListUnderReview::class)
            ->filterTable('awaiting_role', $this->financeStep->role_id)
            ->assertCanSeeTableRecords([$atFinance])
            ->assertCanNotSeeTableRecords([$atManager]);
    }

    public function test_it_can_be_filtered_by_subject_type(): void
    {
        $expenseWorkflow = $this->startWorkflow($this->createExpense(), currentStep: 1);
        $rewardWorkflow = $this->startWorkflow($this->createReward(), currentStep: 1);

        $this->actingAs($this->user);

        Livewire::test(ListUnderReview::class)
            ->filterTable('workflowable_type', Reward::class)
            ->assertCanSeeTableRecords([$rewardWorkflow])
            ->assertCanNotSeeTableRecords([$expenseWorkflow]);
    }

    public function test_it_can_be_searched_by_reference(): void
    {
        $wanted = $this->createExpense();
        $other = $this->createExpense();

        $wantedWorkflow = $this->startWorkflow($wanted, currentStep: 1);
        $otherWorkflow = $this->startWorkflow($other, currentStep: 1);

        $this->actingAs($this->user);

        Livewire::test(ListUnderReview::class)
            ->searchTable($wanted->ref())
            ->assertCanSeeTableRecords([$wantedWorkflow])
            ->assertCanNotSeeTableRecords([$otherWorkflow]);
    }
}
