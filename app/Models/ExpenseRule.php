<?php

namespace App\Models;

use App\Enums\RuleDimension;
use App\Enums\RuleOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExpenseRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruleable_id',
        'ruleable_type',
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

    public function ruleable(): MorphTo
    {
        return $this->morphTo();
    }
}
