<?php

namespace App\Filament\Admin\Resources\ExpenseGroupResource\RelationManagers;

use App\Models\Workflow;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpenseTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenseTypes';

    protected static ?string $title = 'Expense Types';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')->required()->maxLength(255),
            Textarea::make('description')->nullable()->columnSpanFull(),
            Toggle::make('requires_approval')->label('Requires Approval')->live(),
            Toggle::make('requires_attachment')->label('Requires Attachment'),
            Select::make('workflow_id')
                ->label('Workflow')
                ->options(Workflow::query()->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->visible(fn (Get $get): bool => (bool) $get('requires_approval')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                IconColumn::make('requires_approval')->boolean()->label('Approval'),
                IconColumn::make('requires_attachment')->boolean()->label('Attachment'),
                TextColumn::make('workflow.name')->label('Workflow'),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
