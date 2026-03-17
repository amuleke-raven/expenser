<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Workflows\Pages\CreateWorkflow;
use App\Filament\Admin\Resources\Workflows\Pages\ListWorkflows;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkflowResourceTest extends TestCase
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

    public function test_admin_can_list_workflows(): void
    {
        $admin = $this->adminUser();
        $workflows = Workflow::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListWorkflows::class)
            ->assertCanSeeTableRecords($workflows);
    }

    public function test_admin_can_create_workflow(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        Livewire::test(CreateWorkflow::class)
            ->fillForm([
                'name' => 'Test Workflow',
                'description' => 'A test workflow',
                'is_default' => false,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('workflows', ['name' => 'Test Workflow']);
    }

    public function test_workflow_creation_requires_name(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        Livewire::test(CreateWorkflow::class)
            ->fillForm(['name' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }
}
