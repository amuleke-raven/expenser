<?php

namespace App\Filament\Staff\Resources;

use App\Enums\StepActionStatus;
use App\Enums\WorkflowStatus;
use App\Filament\Staff\Resources\UnderReviewResource\Pages;
use App\Models\Expense;
use App\Models\ModelHasWorkflow;
use App\Models\Reward;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UnderReviewResource extends Resource
{
    protected static ?string $model = ModelHasWorkflow::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $modelLabel = 'Item Under Review';

    protected static ?string $pluralModelLabel = 'Under Review';

    protected static ?string $slug = 'under-review';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('model_has_workflows.status', WorkflowStatus::InProgress)
            ->whereHasMorph('workflowable', [Expense::class, Reward::class])
            ->with([
                'workflow.steps.role',
                'stepActions.workflowStep',
                'stepActions.actor',
                'workflowable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    Expense::class => ['user', 'expenseType', 'project', 'currency', 'lineItems', 'attachments'],
                    Reward::class => ['initiatedBy', 'rewardType', 'project', 'currency', 'attachments', 'recipients.user'],
                ]),
            ]);
    }

    /**
     * The workflow step the item is currently sitting at.
     */
    public static function currentStep(ModelHasWorkflow $record): ?WorkflowStep
    {
        return $record->workflow?->steps->firstWhere('order', $record->current_step);
    }

    /**
     * The open approval action for the current step, if one is still awaiting a decision.
     */
    public static function pendingAction(ModelHasWorkflow $record): ?WorkflowStepAction
    {
        $currentStep = self::currentStep($record);

        if (! $currentStep) {
            return null;
        }

        return $record->stepActions
            ->where('workflow_step_id', $currentStep->id)
            ->where('status', StepActionStatus::Pending)
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * The one-based position of the current step within the workflow, and the total number of steps.
     *
     * @return array{position: int, total: int}
     */
    public static function levelPosition(ModelHasWorkflow $record): array
    {
        $steps = $record->workflow?->steps->sortBy('order')->values() ?? collect();

        $index = $steps->search(fn (WorkflowStep $step): bool => $step->order === $record->current_step);

        return [
            'position' => $index === false ? 0 : $index + 1,
            'total' => $steps->count(),
        ];
    }

    /**
     * Every approval level of the workflow with the outcome recorded against it so far.
     *
     * @return array<int, array{level: string, name: string, role: string, status: string, actor: string, actioned_at: string}>
     */
    public static function approvalTrail(ModelHasWorkflow $record): array
    {
        $steps = $record->workflow?->steps->sortBy('order')->values() ?? collect();

        return $steps->map(function (WorkflowStep $step, int $index) use ($record): array {
            $action = $record->stepActions
                ->where('workflow_step_id', $step->id)
                ->sortByDesc('created_at')
                ->first();

            return [
                'level' => 'Level '.($index + 1).($step->order === $record->current_step ? ' (current)' : ''),
                'name' => $step->name,
                'role' => self::roleLabel($step->role?->name),
                'status' => $action?->status->value ?? 'not_started',
                'actor' => $action?->actor?->name ?? '—',
                'actioned_at' => $action?->actioned_at?->format('d M Y, H:i') ?? '—',
            ];
        })->all();
    }

    public static function roleLabel(?string $roleName): string
    {
        return $roleName ? Str::headline($roleName) : '—';
    }

    public static function subjectOf(ModelHasWorkflow $record): Expense|Reward|null
    {
        $subject = $record->workflowable;

        return $subject instanceof Expense || $subject instanceof Reward ? $subject : null;
    }

    /**
     * Narrow the query to the reference typed into the table search, e.g. "EXP-00042" or "42".
     */
    protected static function applyRefSearch(Builder $query, string $search): Builder
    {
        $id = (int) preg_replace('/\D/', '', $search);

        if ($id <= 0) {
            return $query->whereRaw('1 = 0');
        }

        $search = strtoupper(trim($search));
        $expensePrefix = strtoupper((string) config('remoteraven.expense_ref_prefix'));
        $rewardPrefix = strtoupper((string) config('remoteraven.reward_ref_prefix'));

        return $query
            ->where('workflowable_id', $id)
            ->when(
                str_starts_with($search, $expensePrefix),
                fn (Builder $q) => $q->where('workflowable_type', Expense::class)
            )
            ->when(
                str_starts_with($search, $rewardPrefix),
                fn (Builder $q) => $q->where('workflowable_type', Reward::class)
            );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->emptyStateHeading('Nothing is awaiting approval')
            ->emptyStateDescription('Expenses and disbursements waiting on an approval decision will show up here.')
            ->recordAction('viewDetails')
            ->columns([
                TextColumn::make('subject_ref')
                    ->label('Ref')
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::applyRefSearch($query, $search))
                    ->getStateUsing(fn (ModelHasWorkflow $record): string => self::subjectOf($record)?->ref() ?? '—'),

                TextColumn::make('subject_type')
                    ->label('Type')
                    ->badge()
                    ->getStateUsing(fn (ModelHasWorkflow $record): string => match (true) {
                        $record->workflowable instanceof Expense => 'Expense',
                        $record->workflowable instanceof Reward => 'Disbursement',
                        default => 'Unknown',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Expense' => 'info',
                        'Disbursement' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('submitted_by')
                    ->label('Submitted By')
                    ->getStateUsing(fn (ModelHasWorkflow $record): string => match (true) {
                        $record->workflowable instanceof Expense => $record->workflowable->user?->name ?? '—',
                        $record->workflowable instanceof Reward => $record->workflowable->initiatedBy?->name ?? '—',
                        default => '—',
                    }),

                TextColumn::make('category')
                    ->label('Category')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(fn (ModelHasWorkflow $record): string => match (true) {
                        $record->workflowable instanceof Expense => $record->workflowable->expenseType?->name ?? '—',
                        $record->workflowable instanceof Reward => $record->workflowable->rewardType?->name ?? '—',
                        default => '—',
                    }),

                TextColumn::make('project')
                    ->label('Project')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(fn (ModelHasWorkflow $record): string => self::subjectOf($record)?->project?->name ?? '—'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(fn (ModelHasWorkflow $record): string => match (true) {
                        $record->workflowable instanceof Expense => number_format((float) $record->workflowable->total_amount, 2),
                        $record->workflowable instanceof Reward => number_format((float) $record->workflowable->amount, 2),
                        default => '—',
                    }),

                TextColumn::make('currency')
                    ->label('Currency')
                    ->getStateUsing(fn (ModelHasWorkflow $record): string => self::subjectOf($record)?->currency?->code ?? '—'),

                TextColumn::make('pending_level')
                    ->label('Pending Level')
                    ->badge()
                    ->color('warning')
                    ->getStateUsing(function (ModelHasWorkflow $record): string {
                        $step = self::currentStep($record);

                        if (! $step) {
                            return '—';
                        }

                        ['position' => $position, 'total' => $total] = self::levelPosition($record);

                        return "Level {$position} of {$total} — {$step->name}";
                    }),

                TextColumn::make('awaiting_role')
                    ->label('Awaiting')
                    ->getStateUsing(fn (ModelHasWorkflow $record): string => self::roleLabel(self::currentStep($record)?->role?->name)),

                TextColumn::make('waiting_since')
                    ->label('At This Level Since')
                    ->getStateUsing(function (ModelHasWorkflow $record): string {
                        $pendingAction = self::pendingAction($record);

                        return $pendingAction?->created_at?->diffForHumans() ?? '—';
                    })
                    ->description(fn (ModelHasWorkflow $record): ?string => self::pendingAction($record)?->created_at?->format('d M Y, H:i')),

                TextColumn::make('workflow.name')
                    ->label('Workflow')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('started_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('workflowable_type')
                    ->label('Type')
                    ->options([
                        Expense::class => 'Expense',
                        Reward::class => 'Disbursement',
                    ]),

                SelectFilter::make('workflow_id')
                    ->label('Workflow')
                    ->options(fn (): array => Workflow::query()->orderBy('name')->pluck('name', 'id')->all()),

                SelectFilter::make('awaiting_role')
                    ->label('Awaiting')
                    ->options(fn (): array => Role::query()
                        ->whereIn('id', WorkflowStep::query()->select('role_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->map(fn (string $name): string => self::roleLabel($name))
                        ->all()
                    )
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $roleId) => $q->whereHas(
                            'workflow.steps',
                            fn (Builder $steps) => $steps
                                ->where('workflow_steps.role_id', $roleId)
                                ->whereColumn('workflow_steps.order', 'model_has_workflows.current_step')
                        )
                    )),

                SelectFilter::make('submitted_by')
                    ->label('Submitted By')
                    ->searchable()
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $userId) => $q->where(fn (Builder $outer) => $outer
                            ->where(fn (Builder $inner) => $inner
                                ->where('workflowable_type', Expense::class)
                                ->whereIn('workflowable_id', Expense::query()
                                    ->where(fn (Builder $e) => $e->where('user_id', $userId)->orWhere('raised_by', $userId))
                                    ->select('id')
                                )
                            )
                            ->orWhere(fn (Builder $inner) => $inner
                                ->where('workflowable_type', Reward::class)
                                ->whereIn('workflowable_id', Reward::query()->where('initiated_by', $userId)->select('id'))
                            )
                        )
                    )),

                Filter::make('started_at')
                    ->label('Submitted')
                    ->form([
                        DatePicker::make('submitted_from'),
                        DatePicker::make('submitted_until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['submitted_from'], fn (Builder $q, $date) => $q->whereDate('started_at', '>=', $date))
                        ->when($data['submitted_until'], fn (Builder $q, $date) => $q->whereDate('started_at', '<=', $date))
                    ),
            ])
            ->actions([
                Action::make('viewDetails')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->infolist(fn (ModelHasWorkflow $record): array => [
                        ...AccountingReviewResource::detailsInfolist(self::subjectOf($record)),
                        Section::make('Approval Progress')
                            ->description(fn (): string => self::pendingLevelSummary($record))
                            ->schema([
                                RepeatableEntry::make('approval_trail')
                                    ->hiddenLabel()
                                    ->state(fn (): array => self::approvalTrail($record))
                                    ->schema([
                                        TextEntry::make('level')->label('Level'),
                                        TextEntry::make('name')->label('Step'),
                                        TextEntry::make('role')->label('Approver Role'),
                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->formatStateUsing(fn (string $state): string => StepActionStatus::tryFrom($state)?->label() ?? 'Not Started')
                                            ->color(fn (string $state): string => StepActionStatus::tryFrom($state)?->color() ?? 'gray'),
                                        TextEntry::make('actor')->label('Actioned By'),
                                        TextEntry::make('actioned_at')->label('Actioned At'),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver(),
            ]);
    }

    public static function pendingLevelSummary(ModelHasWorkflow $record): string
    {
        $step = self::currentStep($record);

        if (! $step) {
            return 'No approval level is currently pending.';
        }

        ['position' => $position, 'total' => $total] = self::levelPosition($record);

        return "Awaiting level {$position} of {$total} ({$step->name}) with ".self::roleLabel($step->role?->name).'.';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnderReview::route('/'),
        ];
    }
}
