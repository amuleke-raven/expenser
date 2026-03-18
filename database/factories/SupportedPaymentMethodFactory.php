<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Models\SupportedPaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportedPaymentMethod>
 */
class SupportedPaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(PaymentMethodType::cases())->value,
            'is_active' => true,
        ];
    }
}
