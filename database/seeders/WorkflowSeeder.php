<?php

namespace Database\Seeders;

use App\Enums\StepActionType;
use App\Models\ExpenseType;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $managerRole = Role::findByName('manager');
        $adminRole = Role::findByName('admin');

        $workflow = Workflow::firstOrCreate(
            ['name' => 'Standard Approval'],
            ['description' => 'Two-step approval: Manager then Admin']
        );

        WorkflowStep::firstOrCreate(
            ['workflow_id' => $workflow->id, 'order' => 1],
            [
                'name' => 'Manager Review',
                'action_type' => StepActionType::Approval,
                'role_id' => $managerRole->id,
            ]
        );

        WorkflowStep::firstOrCreate(
            ['workflow_id' => $workflow->id, 'order' => 2],
            [
                'name' => 'Admin Sign-off',
                'action_type' => StepActionType::Approval,
                'role_id' => $adminRole->id,
            ]
        );

        // Assign workflow to Travel and Accommodation expense types
        ExpenseType::whereIn('name', ['Travel', 'Accommodation'])->each(function ($type) use ($workflow) {
            $type->update([
                'workflow_id' => $workflow->id,
                'requires_approval' => true,
            ]);
        });
    }
}
