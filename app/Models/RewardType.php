<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_fixed',
        'is_client_based',
        'fixed_amount',
        'fixed_currency_id',
        'requires_approval',
        'workflow_id',
    ];

    protected function casts(): array
    {
        return [
            'is_fixed' => 'boolean',
            'is_client_based' => 'boolean',
            'fixed_amount' => 'decimal:2',
            'requires_approval' => 'boolean',
        ];
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(RewardRule::class);
    }

    public function fixedCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'fixed_currency_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
