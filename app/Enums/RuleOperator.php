<?php

namespace App\Enums;

enum RuleOperator: string
{
    case Gte = 'gte';
    case Lte = 'lte';
    case Eq = 'eq';
    case In = 'in';

    public function label(): string
    {
        return match ($this) {
            self::Gte => 'Greater Than or Equal',
            self::Lte => 'Less Than or Equal',
            self::Eq => 'Equal',
            self::In => 'In',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Gte => 'info',
            self::Lte => 'warning',
            self::Eq => 'success',
            self::In => 'gray',
        };
    }
}
