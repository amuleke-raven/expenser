<?php

namespace App\Models;

use App\Enums\RuleKey;
use Database\Factories\RuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    /** @use HasFactory<RuleFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'key' => RuleKey::class,
        ];
    }

    public static function getValue(RuleKey $key): ?string
    {
        return static::query()->where('key', $key->value)->value('value');
    }

    public static function getDecimalValue(RuleKey $key): ?float
    {
        $value = static::getValue($key);

        return $value !== null ? (float) $value : null;
    }

    public static function getIntValue(RuleKey $key): ?int
    {
        $value = static::getValue($key);

        return $value !== null ? (int) $value : null;
    }
}
