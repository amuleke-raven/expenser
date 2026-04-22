<?php

namespace App\Filament\Staff\Resources;

use App\Enums\ExpenseStatus;
use App\Enums\StepActionStatus;
use App\Filament\Staff\Resources\MyApprovalsResource\Pages;
use App\Models\Expense;
use App\Models\Reward;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepAction;
use App\Services\WorkflowEngine;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Storage;

class MyApprovalsResource extends Resource
{
    protected static ?string $model = WorkflowStepAction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $modelLabel = 'Pending Approval';

    protected static ?string $pluralModelLabel = 'Pending Approvals';

    public static function canAccess(): bool
    {
        $userRoleIds = auth()->user()?->roles->pluck('id') ?? collect();

        if ($userRoleIds->isEmpty()) {
            return false;
        }

        return WorkflowStep::whereIn('role_id', $userRoleIds)->exists();
    }

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
                Action::make('viewDetails')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (WorkflowStepAction $record): bool => $record->modelHasWorkflow?->workflowable instanceof Expense)
                    ->infolist(fn (WorkflowStepAction $record): array => [
                        Section::make('Expense Details')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('ref')
                                    ->label('Reference')
                                    ->getStateUsing(fn () => $record->modelHasWorkflow->workflowable->ref()),
                                TextEntry::make('expense_type')
                                    ->label('Type')
                                    ->getStateUsing(fn () => $record->modelHasWorkflow->workflowable->expenseType?->name ?? '—'),
                                TextEntry::make('project')
                                    ->label('Project')
                                    ->getStateUsing(fn () => $record->modelHasWorkflow->workflowable->project?->name ?? '—'),
                                TextEntry::make('currency')
                                    ->label('Currency')
                                    ->getStateUsing(fn () => $record->modelHasWorkflow->workflowable->currency?->code ?? '—'),
                                TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->getStateUsing(fn () => $record->modelHasWorkflow->workflowable->total_amount)
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->getStateUsing(fn () => $record->modelHasWorkflow->workflowable->status)
                                    ->formatStateUsing(fn (ExpenseStatus $state): string => $state->label())
                                    ->badge()
                                    ->color(fn (ExpenseStatus $state): string => $state->color()),
                                TextEntry::make('submitted_at')
                                    ->label('Submitted At')
                                    ->getStateUsing(fn () => $record->modelHasWorkflow->workflowable->submitted_at?->format('d M Y, H:i')),
                                TextEntry::make('description')
                                    ->label('Description')
                                    ->getStateUsing(fn () => $record->modelHasWorkflow->workflowable->description)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Line Items')
                            ->schema([
                                RepeatableEntry::make('lineItems')
                                    ->hiddenLabel()
                                    ->state(fn () => $record->modelHasWorkflow->workflowable->lineItems)
                                    ->schema([
                                        TextEntry::make('description')->label('Description'),
                                        TextEntry::make('quantity')->numeric(decimalPlaces: 2),
                                        TextEntry::make('unit_price')->money()->label('Unit Price'),
                                        TextEntry::make('total')->money(),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Attachments')
                            ->visible(fn () => $record->modelHasWorkflow->workflowable->attachments->isNotEmpty())
                            ->schema([
                                RepeatableEntry::make('attachments')
                                    ->hiddenLabel()
                                    ->state(fn () => $record->modelHasWorkflow->workflowable->attachments)
                                    ->schema([
                                        TextEntry::make('original_name')
                                            ->label('File')
                                            ->url(fn ($record) => Storage::url($record->path))
                                            ->openUrlInNewTab(),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver(),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Textarea::make('notes')->nullable()->label('Notes'),
                    ])
                    ->action(function (WorkflowStepAction $record, array $data, $livewire) {
                        app(WorkflowEngine::class)->advance(
                            $record,
                            StepActionStatus::Approved,
                            $data['notes'] ?? null,
                            auth()->user()
                        );

                        Notification::make()->title('Approved')->success()->send();

                        $livewire->resetTable();
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
                    ->action(function (WorkflowStepAction $record, array $data, $livewire) {
                        app(WorkflowEngine::class)->advance(
                            $record,
                            StepActionStatus::Rejected,
                            $data['notes'],
                            auth()->user()
                        );

                        Notification::make()->title('Rejected')->warning()->send();

                        $livewire->resetTable();
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
