<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case BankTransfer = 'bank_transfer';
    case MobileMoney = 'mobile_money';
    case Cash = 'cash';
    case CreditCard = 'credit_card';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            PaymentMethodType::BankTransfer => 'Bank Transfer',
            PaymentMethodType::MobileMoney => 'Mobile Money',
            PaymentMethodType::Cash => 'Cash',
            PaymentMethodType::CreditCard => 'Credit Card',
            PaymentMethodType::Cheque => 'Cheque',
        };
    }
}
