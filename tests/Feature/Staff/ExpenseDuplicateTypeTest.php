<?php

namespace Tests\Feature\Staff;

use App\Filament\Staff\Resources\ExpenseResource\Pages\CreateExpense;
use App\Filament\Staff\Resources\ExpenseResource\Pages\EditExpense;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseGroup;
use App\Models\ExpenseType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseDuplicateTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ExpenseType $expenseType;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $group = ExpenseGroup::factory()->create();
        $group->roles()->attach($role);

        $this->expenseType = ExpenseType::factory()->create([
            'expense_group_id' => $group->id,
            'requires_attachment' => false,
        ]);

        $this->currency = Currency::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        $this->actingAs($this->user);
    }

    public function test_cannot_create_a_second_expense_of_the_same_type_on_the_same_date(): void
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'expense_type_id' => $this->expenseType->id,
        ]);

        Livewire::test(CreateExpense::class)
            ->fillForm([
                'expense_type_id' => $this->expenseType->id,
                'currency_id' => $this->currency->id,
                'lineItems' => [
                    ['description' => 'Item', 'quantity' => 1, 'unit_price' => 10, 'total' => 10],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['expense_type_id']);

        $this->assertSame(1, Expense::query()->where('expense_type_id', $this->expenseType->id)->count());
    }

    public function test_can_create_an_expense_when_none_exists_for_that_type_today(): void
    {
        Livewire::test(CreateExpense::class)
            ->fillForm([
                'expense_type_id' => $this->expenseType->id,
                'currency_id' => $this->currency->id,
                'lineItems' => [
                    ['description' => 'Item', 'quantity' => 1, 'unit_price' => 10, 'total' => 10],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Expense::class, [
            'user_id' => $this->user->id,
            'expense_type_id' => $this->expenseType->id,
        ]);
    }

    public function test_can_create_the_same_type_on_a_different_date(): void
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'expense_type_id' => $this->expenseType->id,
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(CreateExpense::class)
            ->fillForm([
                'expense_type_id' => $this->expenseType->id,
                'currency_id' => $this->currency->id,
                'lineItems' => [
                    ['description' => 'Item', 'quantity' => 1, 'unit_price' => 10, 'total' => 10],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_a_different_user_is_not_blocked(): void
    {
        $otherUser = User::factory()->create();

        Expense::factory()->create([
            'user_id' => $otherUser->id,
            'expense_type_id' => $this->expenseType->id,
        ]);

        Livewire::test(CreateExpense::class)
            ->fillForm([
                'expense_type_id' => $this->expenseType->id,
                'currency_id' => $this->currency->id,
                'lineItems' => [
                    ['description' => 'Item', 'quantity' => 1, 'unit_price' => 10, 'total' => 10],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_editing_an_existing_expense_does_not_trigger_the_duplicate_error(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'expense_type_id' => $this->expenseType->id,
        ]);
        $expense->lineItems()->create([
            'description' => 'Item',
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        Livewire::test(EditExpense::class, ['record' => $expense->id])
            ->fillForm([
                'expense_type_id' => $this->expenseType->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
