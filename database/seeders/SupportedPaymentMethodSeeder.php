<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodType;
use App\Models\SupportedPaymentMethod;
use Illuminate\Database\Seeder;

class SupportedPaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PaymentMethodType::cases() as $type) {
            SupportedPaymentMethod::updateOrCreate(
                ['type' => $type->value],
                ['is_active' => true],
            );
        }
    }
}
