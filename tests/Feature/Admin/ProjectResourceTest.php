<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Projects\Pages\CreateProject;
use App\Filament\Admin\Resources\Projects\Pages\EditProject;
use App\Filament\Admin\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
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

    public function test_admin_can_list_projects(): void
    {
        $admin = $this->adminUser();
        $projects = Project::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListProjects::class)
            ->assertCanSeeTableRecords($projects);
    }

    public function test_admin_can_create_project(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Alpha Project',
                'description' => 'Test project',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', ['name' => 'Alpha Project']);
    }

    public function test_admin_can_edit_project(): void
    {
        $admin = $this->adminUser();
        $project = Project::factory()->create(['name' => 'Old Name']);
        $this->actingAs($admin);

        Livewire::test(EditProject::class, ['record' => $project->id])
            ->fillForm(['name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('New Name', $project->fresh()->name);
    }

    public function test_project_name_must_be_unique(): void
    {
        $admin = $this->adminUser();
        Project::factory()->create(['name' => 'Existing Project']);
        $this->actingAs($admin);

        Livewire::test(CreateProject::class)
            ->fillForm(['name' => 'Existing Project'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }

    public function test_admin_can_attach_user_to_project(): void
    {
        $admin = $this->adminUser();
        $project = Project::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Staff]);
        $this->actingAs($admin);

        $project->users()->attach($user);

        $this->assertTrue($project->users->contains($user));
    }

    public function test_admin_can_detach_user_from_project(): void
    {
        $admin = $this->adminUser();
        $project = Project::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Staff]);
        $project->users()->attach($user);
        $this->actingAs($admin);

        $project->users()->detach($user);

        $this->assertFalse($project->fresh()->users->contains($user));
    }
}
