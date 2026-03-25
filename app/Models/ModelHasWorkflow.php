<?php

namespace App\Models;

use App\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelHasWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'workflowable_id',
        'workflowable_type',
        'current_step',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'current_step' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    public function stepActions(): HasMany
    {
        return $this->hasMany(WorkflowStepAction::class);
    }

    public function currentStepModel(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step', 'order')
            ->where('workflow_id', $this->workflow_id);
    }
}
