<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Kenya',        'iso_code' => 'KE'],
            ['name' => 'United States', 'iso_code' => 'US'],
            ['name' => 'United Kingdom', 'iso_code' => 'GB'],
            ['name' => 'United Arab Emirates', 'iso_code' => 'AE'],
            ['name' => 'Germany',      'iso_code' => 'DE'],
            ['name' => 'Canada',       'iso_code' => 'CA'],
            ['name' => 'Australia',    'iso_code' => 'AU'],
            ['name' => 'South Africa', 'iso_code' => 'ZA'],
            ['name' => 'India',        'iso_code' => 'IN'],
            ['name' => 'Nigeria',      'iso_code' => 'NG'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(['iso_code' => $country['iso_code']], $country);
        }
    }
}
