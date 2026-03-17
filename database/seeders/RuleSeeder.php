<?php

namespace Database\Seeders;

use App\Enums\RuleKey;
use App\Models\Rule;
use Illuminate\Database\Seeder;

class RuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'key' => RuleKey::MaxExpenseAmount->value,
                'value' => '10000',
                'description' => 'Maximum allowed expense amount in base currency',
            ],
            [
                'key' => RuleKey::MaxExpenseAge->value,
                'value' => '90',
                'description' => 'Maximum allowed expense age in days',
            ],
        ];

        foreach ($rules as $rule) {
            Rule::query()->updateOrCreate(['key' => $rule['key']], $rule);
        }
    }
}
