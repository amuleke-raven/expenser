<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use Database\Factories\SupportedPaymentMethodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportedPaymentMethod extends Model
{
    /** @use HasFactory<SupportedPaymentMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'is_active' => 'boolean',
        ];
    }

    /** @param Builder<SupportedPaymentMethod> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
