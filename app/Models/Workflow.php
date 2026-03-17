<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\WorkflowFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    /** @use HasFactory<WorkflowFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<WorkflowStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('order');
    }

    /** @return HasMany<RoleWorkflow, $this> */
    public function roleWorkflows(): HasMany
    {
        return $this->hasMany(RoleWorkflow::class);
    }

    /** @param Builder<Workflow> $query */
    public function scopeDefault(Builder $query): void
    {
        $query->where('is_default', true);
    }

    public static function getDefault(): ?self
    {
        return static::query()->default()->first();
    }

    public static function getForRole(UserRole $role): self
    {
        $roleWorkflow = RoleWorkflow::query()
            ->where('role', $role->value)
            ->with('workflow')
            ->first();

        if ($roleWorkflow?->workflow) {
            return $roleWorkflow->workflow;
        }

        $default = static::getDefault();

        if ($default === null) {
            throw new \RuntimeException('No default workflow configured.');
        }

        return $default;
    }
}
