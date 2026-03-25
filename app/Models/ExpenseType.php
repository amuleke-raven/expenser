<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExpenseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_group_id',
        'name',
        'description',
        'requires_approval',
        'requires_attachment',
        'workflow_id',
    ];

    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'requires_attachment' => 'boolean',
        ];
    }

    public function expenseGroup(): BelongsTo
    {
        return $this->belongsTo(ExpenseGroup::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function rules(): MorphMany
    {
        return $this->morphMany(ExpenseRule::class, 'ruleable');
    }
}
