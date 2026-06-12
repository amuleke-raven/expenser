<?php

namespace App\Services;

use App\Enums\StepActionStatus;
use App\Enums\WorkflowStatus;
use App\Events\WorkflowCompleted;
use App\Events\WorkflowInitiated;
use App\Events\WorkflowRejected;
use App\Events\WorkflowStepAdvanced;
use App\Models\Expense;
use App\Models\ModelHasWorkflow;
use App\Models\Reward;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepAction;
use App\Notifications\WorkflowActionRequiredNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class WorkflowEngine
{
    public function initiate(Model $subject, Workflow $workflow): ModelHasWorkflow
    {
        return DB::transaction(function () use ($subject, $workflow) {
            $firstStep = $workflow->steps()->orderBy('order')->first();

            $mhw = ModelHasWorkflow::create([
                'workflow_id' => $workflow->id,
                'workflowable_id' => $subject->id,
                'workflowable_type' => $subject->getMorphClass(),
                'current_step' => $firstStep->order,
                'status' => WorkflowStatus::InProgress,
                'started_at' => now(),
            ]);

            $action = WorkflowStepAction::create([
                'model_has_workflow_id' => $mhw->id,
                'workflow_step_id' => $firstStep->id,
                'status' => StepActionStatus::Pending,
            ]);

            $roleName = Role::find($firstStep->role_id)?->name ?? '';
            if ($roleName) {
                $actors = User::role($roleName)->get();
                Notification::send($actors, new WorkflowActionRequiredNotification($action));
            }

            event(new WorkflowInitiated($mhw));

            return $mhw;
        });
    }

    public function advance(
        WorkflowStepAction $action,
        StepActionStatus $decision,
        ?string $notes,
        User $actor
    ): void {
        if ($decision === StepActionStatus::Approved && $this->isSelfApproval($action, $actor)) {
            throw new AuthorizationException('You cannot approve a request you submitted.');
        }

        DB::transaction(function () use ($action, $decision, $notes, $actor) {
            $action->update([
                'status' => $decision,
                'actor_id' => $actor->id,
                'notes' => $notes,
                'actioned_at' => now(),
            ]);

            $mhw = $action->modelHasWorkflow->load('workflow.steps');

            if ($decision === StepActionStatus::Approved) {
                $nextStep = $mhw->workflow->steps
                    ->where('order', '>', $mhw->current_step)
                    ->sortBy('order')
                    ->first();

                if ($nextStep) {
                    $mhw->update(['current_step' => $nextStep->order]);

                    $newAction = WorkflowStepAction::create([
                        'model_has_workflow_id' => $mhw->id,
                        'workflow_step_id' => $nextStep->id,
                        'status' => StepActionStatus::Pending,
                    ]);

                    $roleName = Role::find($nextStep->role_id)?->name ?? '';
                    if ($roleName) {
                        $actors = User::role($roleName)->get();
                        Notification::send($actors, new WorkflowActionRequiredNotification($newAction));
                    }

                    event(new WorkflowStepAdvanced($mhw, $newAction));
                } else {
                    $mhw->update([
                        'status' => WorkflowStatus::Completed,
                        'completed_at' => now(),
                    ]);
                    event(new WorkflowCompleted($mhw));
                }
            }

            if ($decision === StepActionStatus::Skipped) {
                $nextStep = $mhw->workflow->steps
                    ->where('order', '>', $mhw->current_step)
                    ->sortBy('order')
                    ->first();

                if ($nextStep) {
                    $mhw->update(['current_step' => $nextStep->order]);

                    $newAction = WorkflowStepAction::create([
                        'model_has_workflow_id' => $mhw->id,
                        'workflow_step_id' => $nextStep->id,
                        'status' => StepActionStatus::Pending,
                    ]);

                    $roleName = Role::find($nextStep->role_id)?->name ?? '';
                    if ($roleName) {
                        $actors = User::role($roleName)->get();
                        Notification::send($actors, new WorkflowActionRequiredNotification($newAction));
                    }

                    event(new WorkflowStepAdvanced($mhw, $newAction));
                } else {
                    $mhw->update([
                        'status' => WorkflowStatus::Completed,
                        'completed_at' => now(),
                    ]);
                    event(new WorkflowCompleted($mhw));
                }
            }

            if ($decision === StepActionStatus::Rejected) {
                $mhw->update(['status' => WorkflowStatus::AwaitingResubmission]);
                event(new WorkflowRejected($mhw));
            }
        });
    }

    public function resubmit(ModelHasWorkflow $mhw, User $submitter, ?string $comment): void
    {
        DB::transaction(function () use ($mhw, $submitter, $comment) {
            $mhw->load('workflow.steps');

            $firstStep = $mhw->workflow->steps->sortBy('order')->first();

            WorkflowStepAction::create([
                'model_has_workflow_id' => $mhw->id,
                'workflow_step_id' => $firstStep->id,
                'actor_id' => $submitter->id,
                'status' => StepActionStatus::Resubmitted,
                'notes' => $comment,
                'actioned_at' => now(),
            ]);

            $mhw->update([
                'current_step' => $firstStep->order,
                'status' => WorkflowStatus::InProgress,
                'completed_at' => null,
            ]);

            $newAction = WorkflowStepAction::create([
                'model_has_workflow_id' => $mhw->id,
                'workflow_step_id' => $firstStep->id,
                'status' => StepActionStatus::Pending,
            ]);

            $roleName = Role::find($firstStep->role_id)?->name ?? '';
            if ($roleName) {
                $actors = User::role($roleName)->get();
                Notification::send($actors, new WorkflowActionRequiredNotification($newAction));
            }

            event(new WorkflowStepAdvanced($mhw, $newAction));
        });
    }

    /**
     * Cancel the active workflow for a subject, closing any pending step actions.
     */
    public function cancel(Model $subject, User $actor): void
    {
        DB::transaction(function () use ($subject, $actor) {
            $mhw = ModelHasWorkflow::where('workflowable_id', $subject->id)
                ->where('workflowable_type', $subject->getMorphClass())
                ->whereNotIn('status', [WorkflowStatus::Completed, WorkflowStatus::Cancelled])
                ->first();

            if (! $mhw) {
                return;
            }

            $mhw->stepActions()
                ->where('status', StepActionStatus::Pending)
                ->update([
                    'status' => StepActionStatus::Cancelled,
                    'actor_id' => $actor->id,
                    'notes' => 'Cancelled by requester',
                    'actioned_at' => now(),
                ]);

            $mhw->update([
                'status' => WorkflowStatus::Cancelled,
                'completed_at' => now(),
            ]);
        });
    }

    public function superApprove(WorkflowStepAction $action, ?string $notes, User $actor): void
    {
        DB::transaction(function () use ($action, $notes, $actor) {
            $action->update([
                'status' => StepActionStatus::Approved,
                'actor_id' => $actor->id,
                'notes' => $notes,
                'actioned_at' => now(),
            ]);

            $mhw = $action->modelHasWorkflow->load('workflow.steps');

            $remainingSteps = $mhw->workflow->steps
                ->where('order', '>', $mhw->current_step)
                ->sortBy('order');

            foreach ($remainingSteps as $step) {
                WorkflowStepAction::create([
                    'model_has_workflow_id' => $mhw->id,
                    'workflow_step_id' => $step->id,
                    'actor_id' => $actor->id,
                    'status' => StepActionStatus::Skipped,
                    'notes' => 'Bypassed by super admin approval',
                    'actioned_at' => now(),
                ]);
            }

            $mhw->update([
                'status' => WorkflowStatus::Completed,
                'completed_at' => now(),
            ]);

            event(new WorkflowCompleted($mhw));
        });
    }

    public function getCurrentStep(ModelHasWorkflow $mhw): ?WorkflowStep
    {
        return $mhw->workflow->steps->firstWhere('order', $mhw->current_step);
    }

    public function getPendingActionForUser(Model $subject, User $user): ?WorkflowStepAction
    {
        $mhw = ModelHasWorkflow::where('workflowable_id', $subject->id)
            ->where('workflowable_type', $subject->getMorphClass())
            ->first();

        if (! $mhw) {
            return null;
        }

        $userRoleIds = $user->roles->pluck('id');

        return WorkflowStepAction::where('workflow_step_actions.model_has_workflow_id', $mhw->id)
            ->where('workflow_step_actions.status', StepActionStatus::Pending)
            ->whereHas('workflowStep', fn ($q) => $q->whereIn('role_id', $userRoleIds))
            ->whereExists(fn (Builder $q) => $q
                ->from('model_has_workflows')
                ->join('workflow_steps', fn ($j) => $j
                    ->on('workflow_steps.workflow_id', '=', 'model_has_workflows.workflow_id')
                    ->on('workflow_steps.order', '=', 'model_has_workflows.current_step')
                )
                ->whereColumn('model_has_workflows.id', 'workflow_step_actions.model_has_workflow_id')
                ->whereColumn('workflow_steps.id', 'workflow_step_actions.workflow_step_id')
            )
            ->first();
    }

    /**
     * Determine whether the actor is one of the submitters of the workflow's subject.
     */
    public function isSelfApproval(WorkflowStepAction $action, User $actor): bool
    {
        $subject = $action->modelHasWorkflow?->workflowable;

        return in_array($actor->id, $this->submitterIdsFor($subject), true);
    }

    /**
     * The user ids considered submitters/originators of a workflow subject.
     *
     * @return array<int>
     */
    private function submitterIdsFor(?Model $subject): array
    {
        return match (true) {
            $subject instanceof Expense => array_values(array_filter([$subject->user_id, $subject->raised_by])),
            $subject instanceof Reward => array_values(array_filter([$subject->initiated_by])),
            default => [],
        };
    }
}
