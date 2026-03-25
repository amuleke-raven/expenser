<?php

namespace App\Models;

use App\Enums\StepActionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_has_workflow_id',
        'workflow_step_id',
        'actor_id',
        'status',
        'notes',
        'actioned_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StepActionStatus::class,
            'actioned_at' => 'datetime',
        ];
    }

    public function modelHasWorkflow(): BelongsTo
    {
        return $this->belongsTo(ModelHasWorkflow::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
