<?php

namespace App\Models;

use App\Enums\RuleDimension;
use App\Enums\RuleOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'reward_type_id',
        'dimension',
        'operator',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'dimension' => RuleDimension::class,
            'operator' => RuleOperator::class,
            'value' => 'array',
        ];
    }

    public function rewardType(): BelongsTo
    {
        return $this->belongsTo(RewardType::class);
    }
}
