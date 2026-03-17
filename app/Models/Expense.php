<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'currency_id',
        'merchant_id',
        'workflow_id',
        'preferred_payment_method_id',
        'title',
        'description',
        'amount',
        'expense_date',
        'receipt_path',
        'status',
        'rejection_reason',
        'rejection_comment',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExpenseStatus::class,
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @return BelongsTo<Merchant, $this> */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /** @return BelongsTo<Workflow, $this> */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /** @return BelongsTo<UserPaymentMethod, $this> */
    public function preferredPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(UserPaymentMethod::class, 'preferred_payment_method_id');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return HasMany<ExpenseWorkflowStep, $this> */
    public function workflowSteps(): HasMany
    {
        return $this->hasMany(ExpenseWorkflowStep::class)->orderBy('step_order');
    }

    /** @return HasOne<ExpensePayment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(ExpensePayment::class);
    }

    /** @param Builder<Expense> $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /** @param Builder<Expense> $query */
    public function scopeByStatus(Builder $query, ExpenseStatus $status): void
    {
        $query->where('status', $status->value);
    }

    /** @param Builder<Expense> $query */
    public function scopeAwaitingRoleApproval(Builder $query, UserRole $role): void
    {
        $query->where('status', ExpenseStatus::Submitted->value)
            ->whereHas('workflowSteps', function (Builder $q) use ($role): void {
                $q->where('status', 'pending')
                    ->whereHas('workflowStep', function (Builder $inner) use ($role): void {
                        $inner->where('role', $role->value);
                    });
            });
    }

    public function isDraft(): bool
    {
        return $this->status === ExpenseStatus::Draft;
    }

    public function isSubmitted(): bool
    {
        return $this->status === ExpenseStatus::Submitted;
    }

    public function isApproved(): bool
    {
        return $this->status === ExpenseStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === ExpenseStatus::Rejected;
    }

    public function isProcessing(): bool
    {
        return $this->status === ExpenseStatus::Processing;
    }

    public function isPaid(): bool
    {
        return $this->status === ExpenseStatus::Paid;
    }

    public function currentPendingWorkflowStep(): ?ExpenseWorkflowStep
    {
        return $this->workflowSteps()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();
    }
}
