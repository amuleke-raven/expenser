<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::updateOrCreate(
            ['name' => 'Remote Raven'],
            ['description' => 'Default project for unassigned users.', 'is_active' => true],
        );
    }
}
