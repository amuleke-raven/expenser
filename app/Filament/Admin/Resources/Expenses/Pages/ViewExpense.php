<?php

namespace App\Filament\Admin\Resources\Expenses\Pages;

use App\Enums\ExpenseStatus;
use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Models\Expense;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('overrideStatus')
                ->label('Override Status')
                ->color('warning')
                ->schema([
                    Select::make('status')
                        ->options(collect(ExpenseStatus::cases())->mapWithKeys(fn (ExpenseStatus $s) => [$s->value => $s->label()]))
                        ->required(),
                    Textarea::make('reason')
                        ->label('Reason for override')
                        ->required(),
                ])
                ->action(function (array $data, Expense $record): void {
                    $record->update([
                        'status' => $data['status'],
                        'rejection_reason' => $data['reason'],
                    ]);
                }),
        ];
    }
}
