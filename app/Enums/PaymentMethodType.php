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

    /**
     * @return array<int, array{key: string, label: string, required: bool}>
     */
    public function detailFields(): array
    {
        return match ($this) {
            PaymentMethodType::MobileMoney => [
                ['key' => 'phone_number', 'label' => 'Phone Number', 'required' => true],
                ['key' => 'provider', 'label' => 'Provider', 'required' => true],
            ],
            PaymentMethodType::BankTransfer => [
                ['key' => 'bank_name', 'label' => 'Bank Name', 'required' => true],
                ['key' => 'account_number', 'label' => 'Account Number', 'required' => true],
                ['key' => 'account_name', 'label' => 'Account Name', 'required' => true],
                ['key' => 'branch', 'label' => 'Branch', 'required' => false],
            ],
            PaymentMethodType::CreditCard => [
                ['key' => 'card_reference', 'label' => 'Card Reference', 'required' => false],
            ],
            PaymentMethodType::Cheque => [
                ['key' => 'payee_name', 'label' => 'Payee Name', 'required' => true],
            ],
            PaymentMethodType::Cash => [],
        };
    }
}
