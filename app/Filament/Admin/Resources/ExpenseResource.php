<?php

namespace App\Filament\Admin\Resources;

use App\Enums\ExpenseStatus;
use App\Enums\StepActionStatus;
use App\Filament\Admin\Resources\ExpenseResource\Pages;
use App\Filament\Admin\Resources\ExpenseResource\RelationManagers\AttachmentsRelationManager;
use App\Filament\Admin\Resources\ExpenseResource\RelationManagers\LineItemsRelationManager;
use App\Models\Expense;
use App\Services\WorkflowEngine;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use Filament\Schemas\Components\Section;

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
            TextEntry::make('expenseType.name')->label('Expense Type'),
            TextEntry::make('project.name')->label('Project'),
            TextEntry::make('status')
                ->badge()
                ->color(fn (ExpenseStatus $state): string => $state->color()),
            TextEntry::make('total_amount')->money()->label('Total'),
            TextEntry::make('submitted_at')->dateTime()->label('Submitted'),
            TextEntry::make('rejection_reason')
                ->label('Rejection Reason')
                ->visible(fn ($record) => $record?->status === ExpenseStatus::Rejected),
            TextEntry::make('modelHasWorkflow.currentStepModel.name')
                ->label('Current Workflow Step')
                ->visible(fn ($record) => $record?->status === ExpenseStatus::UnderReview),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ref')
                    ->label('Ref')
                    ->searchable(query: fn ($query, $search) => $query->whereRaw("CONCAT('EXP-', LPAD(id::text, 5, '0')) ILIKE ?", ["%{$search}%"])
                    ),

                TextColumn::make('user.name')
                    ->label('Staff')
                    ->searchable(),

                TextColumn::make('expenseType.name')
                    ->label('Type'),

                TextColumn::make('project.name')
                    ->label('Project'),

                TextColumn::make('total_amount')
                    ->money()
                    ->label('Amount'),

                TextColumn::make('currency.code')
                    ->label('Currency'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ExpenseStatus $state): string => $state->color()),

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
