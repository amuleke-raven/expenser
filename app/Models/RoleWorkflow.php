<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\RoleWorkflowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleWorkflow extends Model
{
    /** @use HasFactory<RoleWorkflowFactory> */
    use HasFactory;

    protected $fillable = [
        'role',
        'workflow_id',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
        ];
    }

    /** @return BelongsTo<Workflow, $this> */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
