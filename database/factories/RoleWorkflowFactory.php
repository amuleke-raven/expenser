<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\RoleWorkflow;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoleWorkflow>
 */
class RoleWorkflowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => fake()->unique()->randomElement(UserRole::cases())->value,
            'workflow_id' => Workflow::factory(),
        ];
    }
}
