<?php

namespace Database\Factories;

use App\Models\ExpenseGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseGroup>
 */
class ExpenseGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'is_default' => false,
        ];
    }
}
