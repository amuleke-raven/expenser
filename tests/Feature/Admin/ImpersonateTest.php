<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImpersonateTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->staffUser = User::factory()->create();
        $this->staffUser->assignRole('staff');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_super_admin_can_impersonate_action_is_visible_for_staff_user(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ListUsers::class)
            ->assertTableActionVisible('impersonate', $this->staffUser);
    }

    public function test_admin_can_impersonate_action_is_visible_for_staff_user(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListUsers::class)
            ->assertTableActionVisible('impersonate', $this->staffUser);
    }

    public function test_impersonate_action_is_hidden_for_other_admins(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('impersonate', $this->admin);
    }

    public function test_impersonate_action_is_hidden_for_other_super_admins(): void
    {
        $anotherSuperAdmin = User::factory()->create();
        $anotherSuperAdmin->assignRole('super_admin');

        $this->actingAs($this->superAdmin);

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('impersonate', $anotherSuperAdmin);
    }

    public function test_impersonate_action_is_hidden_for_self(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('impersonate', $this->superAdmin);
    }

    public function test_staff_user_cannot_impersonate(): void
    {
        $anotherStaff = User::factory()->create();
        $anotherStaff->assignRole('staff');

        $this->actingAs($this->staffUser);

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('impersonate', $anotherStaff);
    }

    public function test_super_admin_can_take_impersonation(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get(route('impersonate', ['id' => $this->staffUser->id]))
            ->assertRedirect('/portal');

        $this->assertEquals($this->staffUser->id, auth()->id());
    }

    public function test_staff_user_cannot_take_impersonation(): void
    {
        $anotherStaff = User::factory()->create();
        $anotherStaff->assignRole('staff');

        $this->actingAs($this->staffUser);

        $this->get(route('impersonate', ['id' => $anotherStaff->id]))
            ->assertForbidden();
    }

    public function test_cannot_impersonate_an_admin(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get(route('impersonate', ['id' => $this->admin->id]))
            ->assertRedirect();

        $this->assertEquals($this->superAdmin->id, auth()->id());
    }

    public function test_leave_impersonation_redirects_to_admin_users(): void
    {
        $this->actingAs($this->superAdmin);
        $this->superAdmin->impersonate($this->staffUser);

        $this->get(route('impersonate.leave'))
            ->assertRedirect('/admin/users');

        $this->assertEquals($this->superAdmin->id, auth()->id());
    }
}
