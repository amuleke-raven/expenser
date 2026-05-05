<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\ExpenseTypeResource\Pages\ListExpenseTypes;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpenseTypeBulkDeleteTest extends TestCase
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

    public function test_bulk_delete_removes_all_selected_expense_types(): void
    {
        /** @var Collection<int, ExpenseType> $expenseTypes */
        $expenseTypes = ExpenseType::factory()->count(3)->create();

        $this->actingAs($this->admin);

        Livewire::test(ListExpenseTypes::class)
            ->callTableBulkAction('delete', $expenseTypes);

        $this->assertDatabaseCount(ExpenseType::class, 0);
    }

    public function test_bulk_delete_only_removes_selected_records(): void
    {
        /** @var Collection<int, ExpenseType> $expenseTypes */
        $expenseTypes = ExpenseType::factory()->count(3)->create();
        $toDelete = $expenseTypes->take(2);
        $toKeep = $expenseTypes->last();

        $this->actingAs($this->admin);

        Livewire::test(ListExpenseTypes::class)
            ->callTableBulkAction('delete', $toDelete);

        foreach ($toDelete as $deleted) {
            $this->assertDatabaseMissing(ExpenseType::class, ['id' => $deleted->id]);
        }

        $this->assertDatabaseHas(ExpenseType::class, ['id' => $toKeep->id]);
    }

    public function test_bulk_delete_skips_expense_types_with_associated_expenses(): void
    {
        $safeType = ExpenseType::factory()->create();
        $linkedType = ExpenseType::factory()->create();
        Expense::factory()->create(['expense_type_id' => $linkedType->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListExpenseTypes::class)
            ->callTableBulkAction('delete', collect([$safeType, $linkedType]));

        $this->assertDatabaseMissing(ExpenseType::class, ['id' => $safeType->id]);
        $this->assertDatabaseHas(ExpenseType::class, ['id' => $linkedType->id]);
    }

    public function test_bulk_delete_of_all_types_with_expenses_deletes_nothing(): void
    {
        $expenseTypes = ExpenseType::factory()->count(2)->create();
        $expenseTypes->each(fn ($type) => Expense::factory()->create(['expense_type_id' => $type->id]));

        $this->actingAs($this->admin);

        Livewire::test(ListExpenseTypes::class)
            ->callTableBulkAction('delete', $expenseTypes);

        foreach ($expenseTypes as $type) {
            $this->assertDatabaseHas(ExpenseType::class, ['id' => $type->id]);
        }
    }
}
