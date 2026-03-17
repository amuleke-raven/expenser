<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
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
            'category_id' => Category::factory(),
            'currency_id' => Currency::factory()->base(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'expense_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'receipt_path' => null,
            'status' => ExpenseStatus::Draft,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Submitted,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Approved,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Rejected,
            'rejection_reason' => 'policy_violation',
            'rejection_comment' => fake()->sentence(),
        ]);
    }
}
