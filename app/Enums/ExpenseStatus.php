<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Processing = 'processing';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            ExpenseStatus::Draft => 'Draft',
            ExpenseStatus::Submitted => 'Submitted',
            ExpenseStatus::Approved => 'Approved',
            ExpenseStatus::Rejected => 'Rejected',
            ExpenseStatus::Processing => 'Processing',
            ExpenseStatus::Paid => 'Paid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            ExpenseStatus::Draft => 'gray',
            ExpenseStatus::Submitted => 'info',
            ExpenseStatus::Approved => 'success',
            ExpenseStatus::Rejected => 'danger',
            ExpenseStatus::Processing => 'warning',
            ExpenseStatus::Paid => 'success',
        };
    }
}
