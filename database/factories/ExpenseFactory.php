<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expense_type_id' => ExpenseType::factory(),
            'currency_id' => Currency::factory(),
            'total_amount' => fake()->randomFloat(2, 10, 1000),
            'description' => fake()->optional()->sentence(),
            'status' => ExpenseStatus::Draft,
        ];
    }
}
