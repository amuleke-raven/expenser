<?php

namespace Tests\Feature\Staff;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentSource;
use App\Enums\PaymentStatus;
use App\Filament\Staff\Resources\PendingPaymentResource\Pages\ListPendingPayments;
use App\Filament\Staff\Resources\ProcessedPaymentResource\Pages\ListProcessedPayments;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\PendingPayment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MarkPaymentPaidTest extends TestCase
{
    use RefreshDatabase;

    private User $financeUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'view_finance', 'guard_name' => 'web']);

        $this->financeUser = User::factory()->create();
        $this->financeUser->givePermissionTo('view_finance');

        Filament::setCurrentPanel(Filament::getPanel('staff'));
    }

    private function createPendingPayment(): PendingPayment
    {
        $currency = Currency::factory()->create();

        $expense = Expense::factory()->create([
            'currency_id' => $currency->id,
            'status' => ExpenseStatus::Approved,
        ]);

        return PendingPayment::create([
            'payable_id' => $expense->id,
            'payable_type' => Expense::class,
            'payment_source' => PaymentSource::Expense,
            'recipient_id' => $expense->user_id,
            'amount' => 250.00,
            'currency_id' => $currency->id,
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function test_pending_payment_is_visible_in_pending_and_absent_from_processed(): void
    {
        $payment = $this->createPendingPayment();

        $this->actingAs($this->financeUser);

        Livewire::test(ListPendingPayments::class)
            ->assertCanSeeTableRecords([$payment]);

        Livewire::test(ListProcessedPayments::class)
            ->assertCanNotSeeTableRecords([$payment]);
    }

    public function test_marking_paid_moves_payment_from_pending_to_processed(): void
    {
        $payment = $this->createPendingPayment();

        $this->actingAs($this->financeUser);

        Livewire::test(ListPendingPayments::class)
            ->callTableAction('mark_paid', $payment);

        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame($this->financeUser->id, $payment->processed_by);
        $this->assertNotNull($payment->processed_at);

        $this->assertSame(ExpenseStatus::Paid, $payment->payable->refresh()->status);

        Livewire::test(ListPendingPayments::class)
            ->assertCanNotSeeTableRecords([$payment]);

        Livewire::test(ListProcessedPayments::class)
            ->assertCanSeeTableRecords([$payment]);
    }
}
