<?php

namespace App\Models;

use App\Enums\RecipientType;
use App\Enums\RewardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'reward_type_id',
        'initiated_by',
        'project_id',
        'amount',
        'currency_id',
        'notes',
        'custom_message',
        'is_billable',
        'payout_date',
        'status',
        'recipient_type',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => RewardStatus::class,
            'is_billable' => 'boolean',
            'recipient_type' => RecipientType::class,
            'amount' => 'decimal:2',
            'payout_date' => 'date',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function rewardType(): BelongsTo
    {
        return $this->belongsTo(RewardType::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(RewardRecipient::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RewardAttachment::class);
    }

    public function modelHasWorkflow(): MorphOne
    {
        return $this->morphOne(ModelHasWorkflow::class, 'workflowable');
    }

    public function ref(): string
    {
        return config('remoteraven.reward_ref_prefix').'-'.
            str_pad((string) $this->id, config('remoteraven.ref_pad_length'), '0', STR_PAD_LEFT);
    }

    public function getRefAttribute(): string
    {
        return $this->ref();
    }
}
