<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar',      'symbol' => '$',  'is_base' => true,  'conversion_rate' => 1.0],
            ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh', 'is_base' => false, 'conversion_rate' => 130.0],
            ['code' => 'GBP', 'name' => 'British Pound',  'symbol' => '£',  'is_base' => false, 'conversion_rate' => 0.79],
            ['code' => 'EUR', 'name' => 'Euro',            'symbol' => '€',  'is_base' => false, 'conversion_rate' => 0.92],
            ['code' => 'AED', 'name' => 'UAE Dirham',      'symbol' => 'AED', 'is_base' => false, 'conversion_rate' => 3.67],
            ['code' => 'INR', 'name' => 'Indian Rupee',    'symbol' => '₹',  'is_base' => false, 'conversion_rate' => 83.5],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱',  'is_base' => false, 'conversion_rate' => 56.0],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(['code' => $currency['code']], $currency);
        }
    }
}
