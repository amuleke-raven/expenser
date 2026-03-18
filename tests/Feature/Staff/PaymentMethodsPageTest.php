<?php

namespace Tests\Feature\Staff;

use App\Enums\PaymentMethodType;
use App\Filament\Staff\Pages\PaymentMethods;
use App\Models\SupportedPaymentMethod;
use App\Models\User;
use App\Models\UserPaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentMethodsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        filament()->setCurrentPanel(filament()->getPanel('staff'));

        SupportedPaymentMethod::factory()->create(['type' => PaymentMethodType::MobileMoney->value, 'is_active' => true]);
        SupportedPaymentMethod::factory()->create(['type' => PaymentMethodType::BankTransfer->value, 'is_active' => true]);
    }

    public function test_user_can_view_their_payment_methods(): void
    {
        $user = User::factory()->create();
        $method = UserPaymentMethod::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(PaymentMethods::class)
            ->assertCanSeeTableRecords(collect([$method]));
    }

    public function test_user_cannot_see_other_users_payment_methods(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherMethod = UserPaymentMethod::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user);

        Livewire::test(PaymentMethods::class)
            ->assertCanNotSeeTableRecords(collect([$otherMethod]));
    }

    public function test_user_can_create_mobile_money_payment_method(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(PaymentMethods::class)
            ->callTableAction('create', data: [
                'type' => PaymentMethodType::MobileMoney->value,
                'label' => 'My MTN',
                'is_default' => false,
                'details' => ['phone_number' => '0551234567', 'provider' => 'MTN'],
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('user_payment_methods', [
            'user_id' => $user->id,
            'label' => 'My MTN',
        ]);
    }

    public function test_user_can_create_bank_transfer_payment_method(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(PaymentMethods::class)
            ->callTableAction('create', data: [
                'type' => PaymentMethodType::BankTransfer->value,
                'label' => 'My Bank',
                'is_default' => false,
                'details' => [
                    'bank_name' => 'GTBank',
                    'account_number' => '1234567890',
                    'account_name' => 'John Doe',
                    'branch' => 'Main',
                ],
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('user_payment_methods', [
            'user_id' => $user->id,
            'label' => 'My Bank',
        ]);
    }

    public function test_setting_default_clears_other_defaults(): void
    {
        $user = User::factory()->create();
        $first = UserPaymentMethod::factory()->create([
            'user_id' => $user->id,
            'is_default' => true,
        ]);
        $second = UserPaymentMethod::factory()->create([
            'user_id' => $user->id,
            'is_default' => false,
        ]);

        $second->update(['is_default' => true]);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_user_can_delete_their_payment_method(): void
    {
        $user = User::factory()->create();
        $method = UserPaymentMethod::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(PaymentMethods::class)
            ->callTableAction('delete', $method)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('user_payment_methods', ['id' => $method->id]);
    }
}
