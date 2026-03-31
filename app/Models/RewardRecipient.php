<?php

namespace App\Models;

use App\Enums\RecipientStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'reward_id',
        'user_id',
        'name',
        'email',
        'status',
        'notified_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RecipientStatus::class,
            'notified_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
