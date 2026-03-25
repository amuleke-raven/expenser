<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::firstOrCreate(
            ['name' => 'Remote Raven'],
            ['is_default' => true, 'is_active' => true, 'client_name' => null]
        );

        Project::firstOrCreate(
            ['name' => 'Client Alpha'],
            ['is_default' => false, 'is_active' => true, 'client_name' => 'Alpha Corp']
        );
    }
}
