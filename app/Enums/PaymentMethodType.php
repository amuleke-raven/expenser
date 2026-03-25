<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case MobileMoney = 'mobile_money';
    case Bank = 'bank';
    case Crypto = 'crypto';
    case Cash = 'cash';
    case Cheque = 'cheque';
    case CreditCard = 'credit_card';

    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile Money',
            self::Bank => 'Bank',
            self::Crypto => 'Crypto',
            self::Cash => 'Cash',
            self::Cheque => 'Cheque',
            self::CreditCard => 'Credit Card',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MobileMoney => 'info',
            self::Bank => 'success',
            self::Crypto => 'warning',
            self::Cash => 'gray',
            self::Cheque => 'gray',
            self::CreditCard => 'info',
        };
    }
}
