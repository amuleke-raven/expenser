<?php

namespace App\Enums;

enum RuleDimension: string
{
    case Amount = 'amount';
    case Country = 'country';
    case Role = 'role';

    public function label(): string
    {
        return match ($this) {
            self::Amount => 'Amount',
            self::Country => 'Country',
            self::Role => 'Role',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Amount => 'info',
            self::Country => 'warning',
            self::Role => 'success',
        };
    }
}
