<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\Project;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $r) => [$r->value => $r->label()]))
                    ->default(UserRole::Staff->value)
                    ->required(),
                Select::make('projects')
                    ->label('Projects')
                    ->multiple()
                    ->relationship('projects', 'name')
                    ->options(Project::query()->active()->pluck('name', 'id'))
                    ->columnSpanFull(),
            ]);
    }
}
