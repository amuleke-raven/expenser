<?php

namespace App\Enums;

enum RuleKey: string
{
    case MaxExpenseAmount = 'max_expense_amount';
    case MaxExpenseAge = 'max_expense_age';

    public function label(): string
    {
        return match ($this) {
            RuleKey::MaxExpenseAmount => 'Max Expense Amount',
            RuleKey::MaxExpenseAge => 'Max Expense Age (Days)',
        };
    }
}
