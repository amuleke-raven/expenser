<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodType;
use App\Models\Country;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $ke = Country::where('iso_code', 'KE')->firstOrFail();

        $mpesa = PaymentMethod::firstOrCreate(
            ['name' => 'M-Pesa'],
            ['type' => PaymentMethodType::MobileMoney, 'is_global' => false, 'country_id' => $ke->id]
        );

        $bank = PaymentMethod::firstOrCreate(
            ['name' => 'Bank Transfer'],
            ['type' => PaymentMethodType::Bank, 'is_global' => true]
        );

        $cash = PaymentMethod::firstOrCreate(
            ['name' => 'Cash'],
            ['type' => PaymentMethodType::Cash, 'is_global' => true]
        );

        $paypal = PaymentMethod::firstOrCreate(
            ['name' => 'PayPal'],
            ['type' => PaymentMethodType::Bank, 'is_global' => true]
        );

        // Assign M-Pesa to staff user (KE), set is_preferred=true
        $staffUser = User::where('email', 'staff@remoteraven.com')->first();
        if ($staffUser) {
            $staffUser->paymentMethods()->syncWithoutDetaching([
                $mpesa->id => ['is_preferred' => true],
            ]);
        }

        // Assign Bank Transfer to all other seeded users, set is_preferred=true
        $otherEmails = [
            'admin@remoteraven.com',
            'manager@remoteraven.com',
            'accountant@remoteraven.com',
            'backoffice@remoteraven.com',
        ];

        foreach ($otherEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->paymentMethods()->syncWithoutDetaching([
                    $bank->id => ['is_preferred' => true],
                ]);
            }
        }
    }
}
