<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\ExpenseTypeResource\Pages\EditExpenseType;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpenseTypeDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'access_admin_panel', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('access_admin_panel');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_expense_type_without_expenses_can_be_deleted(): void
    {
        $expenseType = ExpenseType::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(EditExpenseType::class, ['record' => $expenseType->id])
            ->callAction('delete');

        $this->assertDatabaseMissing(ExpenseType::class, ['id' => $expenseType->id]);
    }

    public function test_expense_type_with_expenses_cannot_be_deleted(): void
    {
        $expenseType = ExpenseType::factory()->create();
        Expense::factory()->create(['expense_type_id' => $expenseType->id]);

        $this->actingAs($this->admin);

        Livewire::test(EditExpenseType::class, ['record' => $expenseType->id])
            ->callAction('delete');

        $this->assertDatabaseHas(ExpenseType::class, ['id' => $expenseType->id]);
    }
}
