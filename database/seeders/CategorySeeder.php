<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Travel', 'color' => '#3b82f6', 'description' => 'Travel and transportation expenses'],
            ['name' => 'Meals', 'color' => '#f59e0b', 'description' => 'Food and dining expenses'],
            ['name' => 'Office Supplies', 'color' => '#10b981', 'description' => 'Office and stationery supplies'],
            ['name' => 'Software', 'color' => '#8b5cf6', 'description' => 'Software licenses and subscriptions'],
            ['name' => 'Training', 'color' => '#ef4444', 'description' => 'Training and professional development'],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(['name' => $category['name']], array_merge($category, ['is_active' => true]));
        }
    }
}
