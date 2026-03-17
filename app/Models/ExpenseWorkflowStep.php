<?php

namespace App\Models;

use App\Enums\WorkflowStepStatus;
use Database\Factories\ExpenseWorkflowStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseWorkflowStep extends Model
{
    /** @use HasFactory<ExpenseWorkflowStepFactory> */
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'workflow_step_id',
        'actioned_by_user_id',
        'status',
        'notes',
        'actioned_at',
        'step_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowStepStatus::class,
            'actioned_at' => 'datetime',
            'step_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Expense, $this> */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /** @return BelongsTo<WorkflowStep, $this> */
    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === WorkflowStepStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === WorkflowStepStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === WorkflowStepStatus::Rejected;
    }
}
