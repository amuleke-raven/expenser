<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_base',
        'conversion_rate',
    ];

    protected function casts(): array
    {
        return [
            'is_base' => 'boolean',
            'conversion_rate' => 'decimal:6',
        ];
    }

    public function scopeBase(Builder $query): Builder
    {
        return $query->where('is_base', true);
    }
}
