<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Workflow;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflow = Workflow::query()->updateOrCreate(
            ['name' => 'Default Approval Workflow'],
            [
                'description' => 'Standard two-step approval workflow',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $steps = [
            ['order' => 1, 'name' => 'Manager Review', 'role' => UserRole::Manager->value],
            ['order' => 2, 'name' => 'Finance Approval', 'role' => UserRole::Finance->value],
        ];

        foreach ($steps as $step) {
            $workflow->steps()->updateOrCreate(
                ['order' => $step['order']],
                $step
            );
        }
    }
}
