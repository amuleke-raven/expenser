<?php

namespace App\Filament\Admin\Resources;

use App\Enums\ExpenseStatus;
use App\Enums\StepActionStatus;
use App\Filament\Admin\Resources\ExpenseResource\Pages;
use App\Filament\Admin\Resources\ExpenseResource\RelationManagers\AttachmentsRelationManager;
use App\Filament\Admin\Resources\ExpenseResource\RelationManagers\LineItemsRelationManager;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Project;
use App\Models\User;
use App\Services\WorkflowEngine;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Expenses';

    protected static ?string $modelLabel = 'Expense';

    protected static ?string $pluralModelLabel = 'Expenses';

    protected static ?string $recordTitleAttribute = 'ref';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('ref')->label('Reference'),
            TextEntry::make('user.name')->label('Staff Member'),
            TextEntry::make('raisedBy.name')
                ->label('Raised By')
                ->visible(fn ($record) => filled($record?->raised_by)),
            TextEntry::make('expenseType.name')->label('Expense Type'),
            TextEntry::make('project.name')->label('Project'),
            TextEntry::make('status')
                ->badge()
                ->color(fn (ExpenseStatus $state): string => $state->color()),
            TextEntry::make('total_amount')->money(fn ($record) => $record->currency?->code ?? 'USD')->label('Total'),
            TextEntry::make('submitted_at')->dateTime()->label('Submitted'),
            IconEntry::make('is_billable')
                ->label('Billable')
                ->boolean(),
            TextEntry::make('rejection_reason')
                ->label('Rejection Reason')
                ->visible(fn ($record) => in_array($record?->status, [ExpenseStatus::Rejected, ExpenseStatus::PendingResubmission])),
            TextEntry::make('modelHasWorkflow.currentStepModel.name')
                ->label('Current Workflow Step')
                ->visible(fn ($record) => $record?->status === ExpenseStatus::UnderReview),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('raiseBackofficeExpense')
                    ->label('Raise Expense on Behalf')
                    ->icon('heroicon-o-user-plus')
                    ->color('info')
                    ->visible(fn (): bool => auth()->user()->can('create-backoffice-expenses'))
                    ->slideOver()
                    ->form([
                        Select::make('user_ids')
                            ->label('Raise For')
                            ->helperText('Select one or more staff members. Excludes you.')
                            ->options(User::query()->where('id', '!=', auth()->id())->orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->required()
                            ->searchable(),

                        Select::make('expense_type_id')
                            ->label('Expense Type')
                            ->options(
                                ExpenseType::query()
                                    ->with('expenseGroup')
                                    ->get()
                                    ->mapWithKeys(fn ($type) => [$type->id => "{$type->expenseGroup->name} — {$type->name}"])
                            )
                            ->required()
                            ->searchable(),

                        Select::make('project_id')
                            ->label('Project')
                            ->options(Project::query()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),

                        Select::make('currency_id')
                            ->label('Currency')
                            ->options(Currency::query()->pluck('code', 'id'))
                            ->required()
                            ->searchable(),

                        Textarea::make('description')
                            ->nullable()
                            ->columnSpanFull(),

                        Toggle::make('is_billable')
                            ->label('Billable')
                            ->default(false),

                        Repeater::make('line_items')
                            ->label('Line Items')
                            ->schema([
                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('total', (float) $get('quantity') * (float) $get('unit_price'))),

                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('total', (float) $get('quantity') * (float) $get('unit_price'))),

                                TextInput::make('total')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->label('Total'),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->addActionLabel('Add Item')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, $livewire): void {
                        $raisedBy = auth()->id();

                        foreach ($data['user_ids'] as $userId) {
                            $expense = Expense::create([
                                'user_id' => $userId,
                                'raised_by' => $raisedBy,
                                'expense_type_id' => $data['expense_type_id'],
                                'project_id' => $data['project_id'] ?? null,
                                'currency_id' => $data['currency_id'],
                                'description' => $data['description'] ?? null,
                                'is_billable' => $data['is_billable'] ?? false,
                                'status' => ExpenseStatus::Draft,
                            ]);

                            foreach ($data['line_items'] as $index => $item) {
                                $expense->lineItems()->create([
                                    'description' => $item['description'],
                                    'quantity' => $item['quantity'],
                                    'unit_price' => $item['unit_price'],
                                    'total' => (float) $item['quantity'] * (float) $item['unit_price'],
                                    'sort_order' => $index,
                                ]);
                            }

                            $expense->recalculateTotal();

                            // Transition to Submitted — observer fires ExpenseSubmitted → workflow
                            $expense->update(['status' => ExpenseStatus::Submitted]);
                        }

                        $count = count($data['user_ids']);

                        Notification::make()
                            ->title($count === 1 ? 'Expense raised successfully' : "{$count} expenses raised successfully")
                            ->success()
                            ->send();

                        $livewire->resetTable();
                    }),
            ])
            ->columns([
                TextColumn::make('ref')
                    ->label('Ref')
                    ->searchable(query: function ($query, string $search) {
                        $prefix = config('remoteraven.expense_ref_prefix').'-';
                        $padLength = (int) config('remoteraven.ref_pad_length');

                        $refExpression = $query->getConnection()->getDriverName() === 'sqlite'
                            ? "? || printf('%0{$padLength}d', id)"
                            : "CONCAT(?, LPAD(id, {$padLength}, '0'))";

                        return $query->whereRaw("{$refExpression} LIKE ?", [$prefix, "%{$search}%"]);
                    }),

                TextColumn::make('user.name')
                    ->label('Staff')
                    ->searchable(),

                TextColumn::make('raisedBy.name')
                    ->label('Raised By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('expenseType.name')
                    ->label('Type'),

                TextColumn::make('project.name')
                    ->label('Project'),

                TextColumn::make('total_amount')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD')
                    ->label('Amount'),

                TextColumn::make('currency.code')
                    ->label('Currency'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ExpenseStatus $state): string => $state->color()),

                IconColumn::make('is_billable')
                    ->label('Billable')
                    ->boolean(),

                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->label('Submitted'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(ExpenseStatus::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    )),

                SelectFilter::make('expense_type_id')
                    ->label('Expense Type')
                    ->relationship('expenseType', 'name'),

                Filter::make('backoffice_raised')
                    ->label('Backoffice Raised')
                    ->query(fn ($query) => $query->whereNotNull('raised_by'))
                    ->toggle(),

                Filter::make('submitted_at')
                    ->form([
                        DatePicker::make('submitted_from'),
                        DatePicker::make('submitted_until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['submitted_from'], fn ($q, $date) => $q->whereDate('submitted_at', '>=', $date))
                        ->when($data['submitted_until'], fn ($q, $date) => $q->whereDate('submitted_at', '<=', $date))
                    ),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (Expense $record): bool {
                        if ($record->status !== ExpenseStatus::UnderReview) {
                            return false;
                        }

                        return self::userCanActOnCurrentStep($record);
                    })
                    ->action(function (Expense $record, $livewire) {
                        $mhw = $record->modelHasWorkflow()->with('workflow.steps')->first();
                        $action = $mhw?->stepActions()->where('status', StepActionStatus::Pending)->first();

                        if ($action) {
                            app(WorkflowEngine::class)->advance($action, StepActionStatus::Approved, null, auth()->user());
                        }

                        $livewire->resetTable();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Expense $record): bool {
                        if ($record->status !== ExpenseStatus::UnderReview) {
                            return false;
                        }

                        return self::userCanActOnCurrentStep($record);
                    })
                    ->form([
                        Textarea::make('rejection_reason')
                            ->required()
                            ->label('Rejection Reason'),
                    ])
                    ->action(function (Expense $record, array $data, $livewire) {
                        $mhw = $record->modelHasWorkflow()->with('workflow.steps')->first();
                        $action = $mhw?->stepActions()->where('status', StepActionStatus::Pending)->first();

                        if ($action) {
                            $record->update(['rejection_reason' => $data['rejection_reason']]);
                            app(WorkflowEngine::class)->advance($action, StepActionStatus::Rejected, $data['rejection_reason'], auth()->user());
                        }

                        $livewire->resetTable();
                    }),

                ViewAction::make(),
            ]);
    }

    protected static function userCanActOnCurrentStep(Expense $record): bool
    {
        $mhw = $record->modelHasWorkflow;

        if (! $mhw) {
            return false;
        }

        $currentStep = app(WorkflowEngine::class)->getCurrentStep($mhw);

        if (! $currentStep?->role_id) {
            return false;
        }

        $roleName = Role::find($currentStep->role_id)?->name;

        return $roleName && auth()->user()->hasRole($roleName);
    }

    public static function getRelations(): array
    {
        return [
            LineItemsRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'view' => Pages\ViewExpense::route('/{record}'),
        ];
    }
}
