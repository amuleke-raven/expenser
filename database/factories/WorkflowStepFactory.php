<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowStep>
 */
class WorkflowStepFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'order' => fake()->numberBetween(1, 10),
            'name' => fake()->words(2, true),
            'role' => fake()->randomElement(UserRole::cases())->value,
        ];
    }
}
