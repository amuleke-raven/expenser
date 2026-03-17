<?php

namespace App\Filament\Staff\Pages;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Enums\WorkflowStepStatus;
use App\Models\ExpenseWorkflowStep;
use App\Models\User;
use App\Services\ExpenseApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApprovalQueue extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.staff.pages.approval-queue';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->role === UserRole::Manager || $user->role === UserRole::Finance;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                /** @var User $user */
                $user = auth()->user();

                return ExpenseWorkflowStep::query()
                    ->where('status', WorkflowStepStatus::Pending->value)
                    ->whereHas('workflowStep', fn (Builder $q) => $q->where('role', $user->role->value))
                    ->whereHas('expense', fn (Builder $q) => $q->where('status', ExpenseStatus::Submitted->value))
                    ->with(['expense.user', 'expense.category', 'expense.currency']);
            })
            ->columns([
                TextColumn::make('expense.title')
                    ->label('Expense')
                    ->searchable(),
                TextColumn::make('expense.user.name')
                    ->label('Submitted By'),
                TextColumn::make('expense.amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state, ExpenseWorkflowStep $record): string => number_format((float) $state, 2).' '.$record->expense->currency?->code),
                TextColumn::make('expense.category.name')
                    ->label('Category'),
                TextColumn::make('workflowStep.name')
                    ->label('Step'),
                TextColumn::make('expense.expense_date')
                    ->label('Date')
                    ->date(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes (optional)'),
                    ])
                    ->action(function (array $data, ExpenseWorkflowStep $record): void {
                        app(ExpenseApprovalService::class)->approve($record, auth()->user(), $data['notes'] ?? null);
                        Notification::make()->title('Step approved.')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->schema([
                        Select::make('reason')
                            ->options([
                                'policy_violation' => 'Policy Violation',
                                'insufficient_documentation' => 'Insufficient Documentation',
                                'duplicate' => 'Duplicate Expense',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Textarea::make('comment')
                            ->label('Comment (optional)'),
                    ])
                    ->action(function (array $data, ExpenseWorkflowStep $record): void {
                        app(ExpenseApprovalService::class)->reject($record, auth()->user(), $data['reason'], $data['comment'] ?? null);
                        Notification::make()->title('Expense rejected.')->warning()->send();
                    }),
            ]);
    }
}
