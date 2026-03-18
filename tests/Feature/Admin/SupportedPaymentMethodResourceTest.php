<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethodType;
use App\Filament\Admin\Resources\SupportedPaymentMethods\Pages\CreateSupportedPaymentMethod;
use App\Filament\Admin\Resources\SupportedPaymentMethods\Pages\EditSupportedPaymentMethod;
use App\Filament\Admin\Resources\SupportedPaymentMethods\Pages\ListSupportedPaymentMethods;
use App\Models\SupportedPaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportedPaymentMethodResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        filament()->setCurrentPanel(filament()->getPanel('admin'));
    }

    private function adminUser(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_list_supported_payment_methods(): void
    {
        $admin = $this->adminUser();
        $methods = collect(PaymentMethodType::cases())->map(
            fn (PaymentMethodType $t) => SupportedPaymentMethod::factory()->create(['type' => $t->value])
        );

        $this->actingAs($admin);

        Livewire::test(ListSupportedPaymentMethods::class)
            ->assertCanSeeTableRecords($methods);
    }

    public function test_admin_can_create_supported_payment_method(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        Livewire::test(CreateSupportedPaymentMethod::class)
            ->fillForm([
                'type' => PaymentMethodType::Cash->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('supported_payment_methods', ['type' => PaymentMethodType::Cash->value]);
    }

    public function test_admin_can_toggle_payment_method_active_status(): void
    {
        $admin = $this->adminUser();
        $method = SupportedPaymentMethod::factory()->create([
            'type' => PaymentMethodType::BankTransfer->value,
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        Livewire::test(EditSupportedPaymentMethod::class, ['record' => $method->id])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($method->fresh()->is_active);
    }

    public function test_payment_method_type_must_be_unique(): void
    {
        $admin = $this->adminUser();
        SupportedPaymentMethod::factory()->create(['type' => PaymentMethodType::Cash->value]);
        $this->actingAs($admin);

        Livewire::test(CreateSupportedPaymentMethod::class)
            ->fillForm([
                'type' => PaymentMethodType::Cash->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['type' => 'unique']);
    }
}
