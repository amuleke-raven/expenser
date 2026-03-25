<?php

namespace App\Filament\Staff\Resources;

use App\Enums\StepActionStatus;
use App\Filament\Staff\Resources\MyApprovalsResource\Pages;
use App\Models\Expense;
use App\Models\Reward;
use App\Models\WorkflowStepAction;
use App\Services\WorkflowEngine;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class MyApprovalsResource extends Resource
{
    protected static ?string $model = WorkflowStepAction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $modelLabel = 'Pending Approval';

    protected static ?string $pluralModelLabel = 'Pending Approvals';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('workflow_step_actions.status', StepActionStatus::Pending)
            ->whereHas('workflowStep', fn ($q) => $q->whereIn('role_id', auth()->user()->roles->pluck('id')))
            ->whereExists(fn (QueryBuilder $q) => $q
                ->from('model_has_workflows')
                ->join('workflow_steps', fn ($j) => $j
                    ->on('workflow_steps.workflow_id', '=', 'model_has_workflows.workflow_id')
                    ->on('workflow_steps.order', '=', 'model_has_workflows.current_step')
                )
                ->whereColumn('model_has_workflows.id', 'workflow_step_actions.model_has_workflow_id')
                ->whereColumn('workflow_steps.id', 'workflow_step_actions.workflow_step_id')
            )
            ->with(['workflowStep', 'modelHasWorkflow.workflowable']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject_ref')
                    ->label('Ref')
                    ->getStateUsing(fn (WorkflowStepAction $record): string => match (true) {
                        $record->modelHasWorkflow?->workflowable instanceof Expense => $record->modelHasWorkflow->workflowable->ref(),
                        $record->modelHasWorkflow?->workflowable instanceof Reward => $record->modelHasWorkflow->workflowable->ref(),
                        default => '—',
                    }),

                TextColumn::make('subject_type')
                    ->label('Type')
                    ->badge()
                    ->getStateUsing(fn (WorkflowStepAction $record): string => match (true) {
                        $record->modelHasWorkflow?->workflowable instanceof Expense => 'Expense',
                        $record->modelHasWorkflow?->workflowable instanceof Reward => 'Reward',
                        default => 'Unknown',
                    })
                    ->color(fn ($state) => match ($state) {
                        'Expense' => 'info',
                        'Reward' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('submitted_by')
                    ->label('Submitted By')
                    ->getStateUsing(fn (WorkflowStepAction $record): string => match (true) {
                        $record->modelHasWorkflow?->workflowable instanceof Expense => $record->modelHasWorkflow->workflowable->user?->name ?? '—',
                        $record->modelHasWorkflow?->workflowable instanceof Reward => $record->modelHasWorkflow->workflowable->initiatedBy?->name ?? '—',
                        default => '—',
                    }),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn (WorkflowStepAction $record): string => match (true) {
                        $record->modelHasWorkflow?->workflowable instanceof Expense => (string) $record->modelHasWorkflow->workflowable->total_amount,
                        $record->modelHasWorkflow?->workflowable instanceof Reward => (string) $record->modelHasWorkflow->workflowable->amount,
                        default => '—',
                    }),

                TextColumn::make('workflowStep.name')->label('Step'),
                TextColumn::make('created_at')->dateTime()->label('Submitted'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Textarea::make('notes')->nullable()->label('Notes'),
                    ])
                    ->action(function (WorkflowStepAction $record, array $data) {
                        app(WorkflowEngine::class)->advance(
                            $record,
                            StepActionStatus::Approved,
                            $data['notes'] ?? null,
                            auth()->user()
                        );

                        Notification::make()->title('Approved')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('notes')
                            ->required()
                            ->label('Rejection Reason'),
                    ])
                    ->action(function (WorkflowStepAction $record, array $data) {
                        app(WorkflowEngine::class)->advance(
                            $record,
                            StepActionStatus::Rejected,
                            $data['notes'],
                            auth()->user()
                        );

                        Notification::make()->title('Rejected')->warning()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyApprovals::route('/'),
        ];
    }
}
