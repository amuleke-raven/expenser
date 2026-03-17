<?php

namespace Tests\Feature;

use App\Enums\ExpenseStatus;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\User;
use App\Services\ExpensePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensePaymentTest extends TestCase
{
    use RefreshDatabase;

    private ExpensePaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExpensePaymentService::class);
    }

    private function createApprovedExpense(): Expense
    {
        $currency = Currency::factory()->base()->create();
        $category = Category::factory()->create();
        $expense = Expense::factory()->approved()->create([
            'currency_id' => $currency->id,
            'category_id' => $category->id,
        ]);
        ExpensePayment::factory()->create(['expense_id' => $expense->id]);

        return $expense;
    }

    public function test_finance_can_generate_report(): void
    {
        $finance = User::factory()->finance()->create();
        $expense = $this->createApprovedExpense();

        $this->service->generateReport($expense, $finance);

        $this->assertEquals(ExpenseStatus::Processing, $expense->fresh()->status);
        $this->assertNotNull($expense->fresh()->payment->report_generated_at);
    }

    public function test_generate_report_requires_approved_status(): void
    {
        $finance = User::factory()->finance()->create();
        $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);

        $this->expectException(\LogicException::class);
        $this->service->generateReport($expense, $finance);
    }

    public function test_confirm_payment_marks_expense_paid(): void
    {
        $finance = User::factory()->finance()->create();
        $expense = $this->createApprovedExpense();
        $this->service->generateReport($expense, $finance);

        $this->service->confirmPayment($expense->fresh(), 'TXN-001', null, 'Paid via bank transfer');

        $this->assertEquals(ExpenseStatus::Paid, $expense->fresh()->status);
        $this->assertNotNull($expense->fresh()->payment->paid_at);
        $this->assertEquals('TXN-001', $expense->fresh()->payment->reference);
    }

    public function test_confirm_payment_requires_processing_status(): void
    {
        $expense = $this->createApprovedExpense();

        $this->expectException(\LogicException::class);
        $this->service->confirmPayment($expense, 'TXN-001');
    }
}
