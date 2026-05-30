<?php

namespace App\Models;

use App\Enums\PaymentSource;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PendingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payable_id',
        'payable_type',
        'payment_source',
        'recipient_id',
        'amount',
        'currency_id',
        'payment_method_id',
        'manual_payment_details',
        'status',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_source' => PaymentSource::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The User recipient — resolved when payment_source is 'expense'.
     */
    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * The RewardRecipient — resolved when payment_source is 'reward'.
     */
    public function rewardRecipient(): BelongsTo
    {
        return $this->belongsTo(RewardRecipient::class, 'recipient_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
