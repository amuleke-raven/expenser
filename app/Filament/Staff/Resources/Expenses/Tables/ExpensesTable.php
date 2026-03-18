<?php

namespace App\Filament\Staff\Resources\Expenses\Tables;

use App\Enums\ExpenseStatus;
use App\Exceptions\ExpenseRuleViolationException;
use App\Models\Expense;
use App\Services\ExpenseSubmissionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state, Expense $record): string => number_format((float) $state, 2).' '.$record->currency?->code),
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ExpenseStatus $state): string => $state->color())
                    ->formatStateUsing(fn (ExpenseStatus $state): string => $state->label()),
                TextColumn::make('expense_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(ExpenseStatus::cases())->mapWithKeys(fn (ExpenseStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Expense $record): bool => $record->isDraft()),
                Action::make('submit')
                    ->label('Submit')
                    ->color('success')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn (Expense $record): bool => $record->isDraft())
                    ->action(function (Expense $record): void {
                        try {
                            app(ExpenseSubmissionService::class)->submit($record);
                            Notification::make()->title('Expense submitted successfully.')->success()->send();
                        } catch (ExpenseRuleViolationException $e) {
                            Notification::make()->title('Submission failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('withdraw')
                    ->label('Withdraw')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Expense $record): bool => $record->isSubmitted())
                    ->action(function (Expense $record): void {
                        $record->workflowSteps()->delete();
                        $record->update([
                            'status' => ExpenseStatus::Draft,
                            'workflow_id' => null,
                        ]);
                        Notification::make()->title('Expense withdrawn to draft.')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
