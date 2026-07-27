<?php

namespace App\Models;

use App\Enums\StepActionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'name',
        'order',
        'action_type',
        'role_id',
    ];

    protected function casts(): array
    {
        return [
            'action_type' => StepActionType::class,
            'order' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowStepAction::class);
    }
}
