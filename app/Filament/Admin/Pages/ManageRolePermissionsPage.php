<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ManageRolePermissionsPage extends Page
{
    protected string $view = 'filament.admin.pages.manage-role-permissions-page';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Users';

    protected static ?string $navigationLabel = 'Role Permissions';

    protected static ?int $navigationSort = 99;

    public ?int $role_id = null;

    public array $permission_ids = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'role_id' => null,
            'permission_ids' => [],
        ]);
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('role_id')
                ->label('Role')
                ->options(Role::orderBy('name')->pluck('name', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(function ($state) {
                    if ($state) {
                        $this->permission_ids = Role::find($state)
                            ?->permissions->pluck('id')->map(fn ($id) => (string) $id)->toArray() ?? [];
                    } else {
                        $this->permission_ids = [];
                    }
                }),

            CheckboxList::make('permission_ids')
                ->label('Permissions')
                ->options(
                    Permission::orderBy('name')->pluck('name', 'id')
                        ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
                )
                ->columns(3)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => filled($get('role_id'))),
        ])->statePath('');
    }

    public function save(): void
    {
        if (! $this->role_id) {
            return;
        }

        $role = Role::findById((int) $this->role_id);
        $role->syncPermissions(array_map('intval', $this->permission_ids));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Notification::make()
            ->title('Permissions updated for '.$role->name)
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
