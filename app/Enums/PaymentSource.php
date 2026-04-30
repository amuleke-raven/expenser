<?php

namespace App\Enums;

enum PaymentSource: string
{
    case Expense = 'expense';
    case Reward = 'reward';

    public function label(): string
    {
        return match ($this) {
            self::Expense => 'Expense',
            self::Reward => 'Disbursement',
        };
    }
}
