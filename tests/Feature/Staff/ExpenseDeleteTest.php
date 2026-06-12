<?php

namespace Tests\Feature\Staff;

use App\Enums\ExpenseStatus;
use App\Filament\Staff\Resources\ExpenseResource\Pages\ListExpenses;
use App\Models\Expense;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('staff'));
    }

    public function test_draft_expense_can_be_deleted(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Draft,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->callTableAction('delete', $expense);

        $this->assertDatabaseMissing(Expense::class, ['id' => $expense->id]);
    }

    public function test_submitted_expense_cannot_be_deleted(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Submitted,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->assertTableActionHidden('delete', $expense);

        $this->assertDatabaseHas(Expense::class, ['id' => $expense->id]);
    }

    public function test_under_review_expense_cannot_be_deleted(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::UnderReview,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->assertTableActionHidden('delete', $expense);

        $this->assertDatabaseHas(Expense::class, ['id' => $expense->id]);
    }

    public function test_rejected_expense_can_be_deleted(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Rejected,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->callTableAction('delete', $expense);

        $this->assertDatabaseMissing(Expense::class, ['id' => $expense->id]);
    }

    public function test_approved_expense_cannot_be_deleted(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Approved,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->assertTableActionHidden('delete', $expense);

        $this->assertDatabaseHas(Expense::class, ['id' => $expense->id]);
    }

    public function test_paid_expense_cannot_be_deleted(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'status' => ExpenseStatus::Paid,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListExpenses::class)
            ->assertTableActionHidden('delete', $expense);

        $this->assertDatabaseHas(Expense::class, ['id' => $expense->id]);
    }
}
