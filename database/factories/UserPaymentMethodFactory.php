<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Models\User;
use App\Models\UserPaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPaymentMethod>
 */
class UserPaymentMethodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(PaymentMethodType::cases())->value,
            'label' => fake()->words(2, true),
            'details' => ['account_number' => fake()->bankAccountNumber()],
            'is_default' => false,
        ];
    }
}
