<?php

namespace App\Services;

use App\Enums\StepActionStatus;
use App\Enums\WorkflowStatus;
use App\Events\WorkflowCompleted;
use App\Events\WorkflowInitiated;
use App\Events\WorkflowRejected;
use App\Events\WorkflowStepAdvanced;
use App\Models\ModelHasWorkflow;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepAction;
use App\Notifications\WorkflowActionRequiredNotification;
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

            if ($decision === StepActionStatus::Rejected) {
                $mhw->update([
                    'status' => WorkflowStatus::Cancelled,
                    'completed_at' => now(),
                ]);
                event(new WorkflowRejected($mhw));
            }
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
}
