<?php

namespace Database\Seeders;

use App\Models\ExpenseGroup;
use App\Models\ExpenseType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ExpenseGroupSeeder extends Seeder
{
    public function run(): void
    {
        $generalGroup = ExpenseGroup::firstOrCreate(
            ['name' => 'General'],
            ['description' => 'General expenses', 'is_default' => true]
        );

        $types = [
            ['name' => 'Health & Wellness', 'requires_approval' => true,  'requires_attachment' => true],
            ['name' => 'Office Supplies',  'requires_approval' => false, 'requires_attachment' => false],
        ];

        foreach ($types as $type) {
            ExpenseType::firstOrCreate(
                ['name' => $type['name'], 'expense_group_id' => $generalGroup->id],
                array_merge($type, ['expense_group_id' => $generalGroup->id])
            );
        }

        // Assign "General" group to roles: staff, manager
        $staffRole = Role::findByName('staff');
        $managerRole = Role::findByName('manager');

        $generalGroup->roles()->syncWithoutDetaching([$staffRole->id, $managerRole->id]);
    }
}
