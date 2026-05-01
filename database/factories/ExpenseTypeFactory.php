<?php

namespace Database\Factories;

use App\Models\ExpenseGroup;
use App\Models\ExpenseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseType>
 */
class ExpenseTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'expense_group_id' => ExpenseGroup::factory(),
            'requires_approval' => fake()->boolean(),
            'requires_attachment' => fake()->boolean(),
            'workflow_id' => null,
        ];
    }
}
