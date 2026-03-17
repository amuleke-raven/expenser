<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
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

    public function test_admin_can_list_users(): void
    {
        $admin = $this->adminUser();
        $users = User::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords($users);
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($staff)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Staff',
                'email' => 'newstaff@example.com',
                'password' => 'password',
                'role' => UserRole::Staff->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['email' => 'newstaff@example.com']);
    }

    public function test_admin_can_edit_user(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['role' => UserRole::Staff]);
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Updated Name',
                'email' => $user->email,
                'role' => UserRole::Manager->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(UserRole::Manager, $user->fresh()->role);
    }
}
