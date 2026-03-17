<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            RuleSeeder::class,
            WorkflowSeeder::class,
            UserSeeder::class,
        ]);
    }
}
