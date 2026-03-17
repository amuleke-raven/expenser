<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'Urgent', 'color' => '#ef4444'],
            ['name' => 'Recurring', 'color' => '#f59e0b'],
            ['name' => 'Client', 'color' => '#3b82f6'],
            ['name' => 'Internal', 'color' => '#6366f1'],
        ];

        foreach ($tags as $tag) {
            Tag::query()->updateOrCreate(['name' => $tag['name']], array_merge($tag, ['is_active' => true]));
        }
    }
}
