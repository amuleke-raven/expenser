<?php

namespace App\Filament\Staff\Resources;

use App\Enums\ExpenseStatus;
use App\Enums\RecipientStatus;
use App\Enums\RewardStatus;
use App\Enums\StepActionStatus;
use App\Filament\Staff\Resources\AccountingReviewResource\Pages;
use App\Models\Expense;
use App\Models\Reward;
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
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Storage;

class AccountingReviewResource extends Resource
{
    protected static ?string $model = WorkflowStepAction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $modelLabel = 'Accounting Review';

    protected static ?string $pluralModelLabel = 'Accounting Review';

    protected static ?string $slug = 'accounting-review';

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_finance');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('workflow_step_actions.status', StepActionStatus::Pending)
            ->whereExists(fn (QueryBuilder $q) => $q
                ->from('model_has_workflows')
                ->join('workflow_steps', fn ($j) => $j
                    ->on('workflow_steps.workflow_id', '=', 'model_has_workflows.workflow_id')
                    ->on('workflow_steps.order', '=', 'model_has_workflows.current_step')
                )
                ->whereColumn('model_has_workflows.id', 'workflow_step_actions.model_has_workflow_id')
                ->whereColumn('workflow_steps.id', 'workflow_step_actions.workflow_step_id')
            )
            ->whereHas('workflowStep', fn (Builder $q) => $q
                ->whereIn('role_id', fn ($sub) => $sub
                    ->select('role_id')
                    ->from('role_has_permissions')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('permissions.name', 'view_finance')
                )
            )
            ->with([
                'workflowStep',
                'modelHasWorkflow.workflowable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    Expense::class => ['user', 'expenseType', 'project', 'currency', 'lineItems', 'attachments'],
                    Reward::class => ['initiatedBy', 'rewardType', 'project', 'currency', 'attachments', 'recipients.user'],
                ]),
            ]);
    }

    /**
     * Detail view (slide-over infolist) for the review subject, whether an Expense or a Disbursement.
     *
     * @return array<int, Section>
     */
    public static function detailsInfolist(Expense|Reward|null $subject): array
    {
        if ($subject instanceof Expense) {
            return [
                Section::make('Expense Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('ref')
                            ->label('Reference')
                            ->getStateUsing(fn () => $subject->ref()),
                        TextEntry::make('expense_type')
                            ->label('Type')
                            ->getStateUsing(fn () => $subject->expenseType?->name ?? '—'),
                        TextEntry::make('project')
                            ->label('Project')
                            ->getStateUsing(fn () => $subject->project?->name ?? '—'),
                        TextEntry::make('currency')
                            ->label('Currency')
                            ->getStateUsing(fn () => $subject->currency?->code ?? '—'),
                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->getStateUsing(fn () => $subject->total_amount)
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('status')
                            ->label('Status')
                            ->getStateUsing(fn () => $subject->status)
                            ->formatStateUsing(fn (ExpenseStatus $state): string => $state->label())
                            ->badge()
                            ->color(fn (ExpenseStatus $state): string => $state->color()),
                        TextEntry::make('submitted_at')
                            ->label('Submitted At')
                            ->getStateUsing(fn () => $subject->submitted_at?->format('d M Y, H:i')),
                        TextEntry::make('description')
                            ->label('Description')
                            ->getStateUsing(fn () => $subject->description)
                            ->columnSpanFull(),
                    ]),
                Section::make('Line Items')
                    ->schema([
                        RepeatableEntry::make('lineItems')
                            ->hiddenLabel()
                            ->state(fn () => $subject->lineItems)
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
                    ->visible(fn () => $subject->attachments->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->state(fn () => $subject->attachments)
                            ->schema([
                                TextEntry::make('original_name')
                                    ->label('File')
                                    ->url(fn ($record) => Storage::url($record->path))
                                    ->openUrlInNewTab(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ];
        }

        if ($subject instanceof Reward) {
            return [
                Section::make('Disbursement Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('ref')
                            ->label('Reference')
                            ->getStateUsing(fn () => $subject->ref()),
                        TextEntry::make('reward_type')
                            ->label('Reward Type')
                            ->getStateUsing(fn () => $subject->rewardType?->name ?? '—'),
                        TextEntry::make('project')
                            ->label('Project')
                            ->getStateUsing(fn () => $subject->project?->name ?? '—'),
                        TextEntry::make('initiated_by')
                            ->label('Initiated By')
                            ->getStateUsing(fn () => $subject->initiatedBy?->name ?? '—'),
                        TextEntry::make('currency')
                            ->label('Currency')
                            ->getStateUsing(fn () => $subject->currency?->code ?? '—'),
                        TextEntry::make('amount')
                            ->label('Amount')
                            ->getStateUsing(fn () => $subject->amount)
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('status')
                            ->label('Status')
                            ->getStateUsing(fn () => $subject->status->label())
                            ->badge()
                            ->color(fn () => $subject->status instanceof RewardStatus ? $subject->status->color() : 'gray'),
                        TextEntry::make('payout_date')
                            ->label('Payout Date')
                            ->getStateUsing(fn () => $subject->payout_date?->format('d M Y') ?? '—'),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->getStateUsing(fn () => $subject->notes ?? '—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Recipients')
                    ->visible(fn () => $subject->recipients->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('recipients')
                            ->hiddenLabel()
                            ->state(fn () => $subject->recipients)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->getStateUsing(fn ($record) => $record->user?->name ?? $record->name ?? '—'),
                                TextEntry::make('email')
                                    ->label('Email')
                                    ->getStateUsing(fn ($record) => $record->user?->email ?? $record->email ?? '—'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->formatStateUsing(fn (RecipientStatus $state) => $state->label())
                                    ->badge()
                                    ->color(fn (RecipientStatus $state) => $state->color()),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Attachments')
                    ->visible(fn () => $subject->attachments->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->state(fn () => $subject->attachments)
                            ->schema([
                                TextEntry::make('original_name')
                                    ->label('File')
                                    ->url(fn ($record) => Storage::url($record->path))
                                    ->openUrlInNewTab(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ];
        }

        return [
            Section::make()->schema([
                TextEntry::make('notice')
                    ->hiddenLabel()
                    ->getStateUsing(fn () => 'No details available.'),
            ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordAction('viewDetails')
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
                        $record->modelHasWorkflow?->workflowable instanceof Reward => 'Disbursement',
                        default => 'Unknown',
                    })
                    ->color(fn ($state) => match ($state) {
                        'Expense' => 'info',
                        'Disbursement' => 'success',
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
                    ->infolist(fn (WorkflowStepAction $record): array => self::detailsInfolist($record->modelHasWorkflow?->workflowable))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver(),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (): bool => auth()->user()->hasRole('super_admin'))
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

                Action::make('superApprove')
                    ->label('Force Approve')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()->hasRole('super_admin'))
                    ->requiresConfirmation()
                    ->modalHeading('Force Approve')
                    ->modalDescription('This will bypass all remaining approval steps and immediately mark this as approved.')
                    ->form([
                        Textarea::make('notes')->nullable()->label('Notes'),
                    ])
                    ->action(function (WorkflowStepAction $record, array $data, $livewire) {
                        app(WorkflowEngine::class)->superApprove(
                            $record,
                            $data['notes'] ?? null,
                            auth()->user()
                        );

                        Notification::make()->title('Force approved — all steps bypassed')->success()->send();

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
            'index' => Pages\ListAccountingReviews::route('/'),
        ];
    }
}
