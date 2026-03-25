<?php

namespace App\Filament\Admin\Resources\ExpenseGroupResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class AssignedRolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $title = 'Assigned Roles';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Role'),
            ])
            ->headerActions([
                Action::make('sync_roles')
                    ->label('Assign Roles')
                    ->icon('heroicon-o-plus')
                    ->form([
                        CheckboxList::make('role_ids')
                            ->label('Roles')
                            ->options(Role::query()->pluck('name', 'id'))
                            ->default(fn () => $this->getOwnerRecord()->roles->pluck('id')->toArray()),
                    ])
                    ->action(function (array $data) {
                        $this->getOwnerRecord()->roles()->sync($data['role_ids'] ?? []);
                        Notification::make()->title('Roles updated')->success()->send();
                    }),
            ])
            ->actions([]);
    }
}
