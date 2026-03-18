<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use Database\Factories\UserPaymentMethodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{
    /** @use HasFactory<UserPaymentMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'details',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'details' => 'array',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (UserPaymentMethod $method): void {
            if ($method->is_default) {
                static::query()
                    ->where('user_id', $method->user_id)
                    ->where('id', '!=', $method->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    /** @param Builder<UserPaymentMethod> $query */
    public function scopeDefault(Builder $query): void
    {
        $query->where('is_default', true);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
