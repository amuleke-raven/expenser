<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'raised_by',
        'expense_type_id',
        'project_id',
        'currency_id',
        'total_amount',
        'description',
        'is_billable',
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExpenseStatus::class,
            'is_billable' => 'boolean',
            'total_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(ExpenseLineItem::class)->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class);
    }

    public function modelHasWorkflow(): MorphOne
    {
        return $this->morphOne(ModelHasWorkflow::class, 'workflowable');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus(Builder $query, ExpenseStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', ExpenseStatus::Submitted->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ExpenseStatus::Approved->value);
    }

    public function recalculateTotal(): void
    {
        $this->total_amount = $this->lineItems()->sum('total');
        $this->saveQuietly();
    }

    public function ref(): string
    {
        return config('remoteraven.expense_ref_prefix').'-'.
            str_pad((string) $this->id, config('remoteraven.ref_pad_length'), '0', STR_PAD_LEFT);
    }

    public function getRefAttribute(): string
    {
        return $this->ref();
    }
}
