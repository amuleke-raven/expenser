<?php

namespace Database\Factories;

use App\Enums\RuleKey;
use App\Models\Rule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rule>
 */
class RuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->randomElement(RuleKey::cases())->value,
            'value' => (string) fake()->numberBetween(100, 10000),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
