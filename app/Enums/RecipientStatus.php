<?php

namespace App\Enums;

enum RecipientStatus: string
{
    case Pending = 'pending';
    case Notified = 'notified';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Notified => 'Notified',
            self::Paid => 'Paid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Notified => 'info',
            self::Paid => 'success',
        };
    }
}
