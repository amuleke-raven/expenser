<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\WorkflowStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    /** @use HasFactory<WorkflowStepFactory> */
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'order',
        'name',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'order' => 'integer',
        ];
    }

    /** @return BelongsTo<Workflow, $this> */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
