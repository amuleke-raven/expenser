<?php

namespace App\Filament\Admin\Resources\WorkflowResource\RelationManagers;

use App\Enums\StepActionType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class WorkflowStepsRelationManager extends RelationManager
{
    protected static string $relationship = 'steps';

    protected static ?string $title = 'Steps';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('order')
                ->required()
                ->numeric()
                ->label('Order'),

            TextInput::make('name')
                ->required()
                ->maxLength(255),

            Select::make('action_type')
                ->options(collect(StepActionType::cases())->mapWithKeys(
                    fn ($case) => [$case->value => $case->label()]
                ))
                ->required()
                ->label('Action Type'),

            Select::make('role_id')
                ->options(Role::query()->pluck('name', 'id'))
                ->searchable()
                ->label('Role')
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->reorderable('order')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('name'),

                TextColumn::make('action_type')
                    ->badge()
                    ->color(fn (StepActionType $state): string => $state->color()),

                TextColumn::make('role_id')
                    ->label('Role')
                    ->formatStateUsing(fn ($state) => Role::find($state)?->name ?? '—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
