<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Expense;
use App\Models\PendingPayment;
use App\Models\RewardRecipient;

class PaymentPostingService
{
    public function postExpense(Expense $expense): PendingPayment
    {
        $existing = PendingPayment::where('payable_type', Expense::class)
            ->where('payable_id', $expense->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return PendingPayment::create([
            'payable_id' => $expense->id,
            'payable_type' => Expense::class,
            'user_id' => $expense->user_id,
            'amount' => $expense->total_amount,
            'currency_id' => $expense->currency_id,
            'payment_method_id' => $expense->user->preferredPaymentMethod()?->id,
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function postReward(RewardRecipient $recipient): PendingPayment
    {
        $existing = PendingPayment::where('payable_type', RewardRecipient::class)
            ->where('payable_id', $recipient->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return PendingPayment::create([
            'payable_id' => $recipient->id,
            'payable_type' => RewardRecipient::class,
            'user_id' => $recipient->user_id,
            'amount' => $recipient->reward->amount,
            'currency_id' => $recipient->reward->currency_id,
            'payment_method_id' => $recipient->user?->preferredPaymentMethod()?->id,
            'status' => PaymentStatus::Pending,
        ]);
    }
}
