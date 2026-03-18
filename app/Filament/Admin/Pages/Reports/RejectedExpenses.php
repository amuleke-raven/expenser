<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\ExpenseStatus;
use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use Filament\Actions\ViewAction;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RejectedExpenses extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.reports.rejected-expenses';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedXCircle;

    protected static ?string $navigationLabel = 'Rejected Expenses';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Expense::query()
                ->byStatus(ExpenseStatus::Rejected)
                ->with(['user', 'category', 'currency']))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->formatStateUsing(fn ($state, Expense $record): string => number_format((float) $state, 2).' '.$record->currency?->code)
                    ->sortable(),
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('rejection_reason')
                    ->label('Reason'),
                TextColumn::make('expense_date')
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Expense $record): string => ExpenseResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
