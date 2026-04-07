<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            CurrencySeeder::class,
            ProjectSeeder::class,
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            PaymentMethodSeeder::class,
            ExpenseGroupSeeder::class,
            WorkflowSeeder::class,
        ]);

        if (app()->environment(['local', 'staging'])) {
            $this->call([
                TicketSystemSeeder::class,
            ]);
        }
    }
}
