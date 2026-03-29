<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WorkflowResource\Pages;
use App\Filament\Admin\Resources\WorkflowResource\RelationManagers\WorkflowStepsRelationManager;
use App\Models\Workflow;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $modelLabel = 'Workflow';

    protected static ?string $pluralModelLabel = 'Workflows';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Workflow Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->nullable()
                        ->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('steps_count')
                    ->counts('steps')
                    ->label('Steps'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            WorkflowStepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit' => Pages\EditWorkflow::route('/{record}/edit'),
        ];
    }
}
