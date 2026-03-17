<?php

namespace Tests\Feature\Staff;

use App\Enums\UserRole;
use App\Filament\Staff\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Staff\Resources\Expenses\Pages\ListExpenses;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        filament()->setCurrentPanel(filament()->getPanel('staff'));
    }

    public function test_staff_sees_only_own_expenses(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $other = User::factory()->create(['role' => UserRole::Staff]);
        $currency = Currency::factory()->base()->create();
        $category = Category::factory()->create();

        $ownExpense = Expense::factory()->create(['user_id' => $staff->id, 'currency_id' => $currency->id, 'category_id' => $category->id]);
        $otherExpense = Expense::factory()->create(['user_id' => $other->id, 'currency_id' => $currency->id, 'category_id' => $category->id]);

        $this->actingAs($staff);

        Livewire::test(ListExpenses::class)
            ->assertCanSeeTableRecords(collect([$ownExpense]))
            ->assertCanNotSeeTableRecords(collect([$otherExpense]));
    }

    public function test_staff_can_create_expense(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $currency = Currency::factory()->base()->create();
        $category = Category::factory()->create();

        $this->actingAs($staff);

        Livewire::test(CreateExpense::class)
            ->fillForm([
                'title' => 'Test Expense',
                'amount' => 150.00,
                'currency_id' => $currency->id,
                'category_id' => $category->id,
                'expense_date' => now()->subDays(2)->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('expenses', [
            'user_id' => $staff->id,
            'title' => 'Test Expense',
            'status' => 'draft',
        ]);
    }

    public function test_submitted_expense_cannot_be_edited(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $currency = Currency::factory()->base()->create();
        $category = Category::factory()->create();
        $expense = Expense::factory()->submitted()->create([
            'user_id' => $staff->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($staff)
            ->get('/app/expenses/'.$expense->id.'/edit')
            ->assertForbidden();
    }
}
