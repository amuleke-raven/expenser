<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpensePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpensePayment>
 */
class ExpensePaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory()->approved(),
            'processed_by_user_id' => null,
            'payment_method_id' => null,
            'reference' => null,
            'report_generated_at' => null,
            'paid_at' => null,
            'notes' => null,
        ];
    }
}
