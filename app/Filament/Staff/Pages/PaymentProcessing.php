<?php

namespace App\Filament\Staff\Pages;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\User;
use App\Models\UserPaymentMethod;
use App\Services\ExpensePaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentProcessing extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.staff.pages.payment-processing';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public string $activeTab = 'approved';

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->role === UserRole::Finance;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return Expense::query()
                    ->whereIn('status', [ExpenseStatus::Approved->value, ExpenseStatus::Processing->value])
                    ->with(['user', 'currency', 'category', 'payment']);
            })
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('user.name'),
                TextColumn::make('amount')
                    ->formatStateUsing(fn ($state, Expense $record): string => number_format((float) $state, 2).' '.$record->currency?->code),
                TextColumn::make('category.name'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ExpenseStatus $state): string => $state->color())
                    ->formatStateUsing(fn (ExpenseStatus $state): string => $state->label()),
                TextColumn::make('expense_date')
                    ->date(),
            ])
            ->recordActions([
                Action::make('generateReport')
                    ->label('Generate Report')
                    ->color('info')
                    ->visible(fn (Expense $record): bool => $record->isApproved())
                    ->requiresConfirmation()
                    ->action(function (Expense $record): void {
                        app(ExpensePaymentService::class)->generateReport($record, auth()->user());
                        Notification::make()->title('Report generated. Expense is now processing.')->success()->send();
                    }),
                Action::make('confirmPayment')
                    ->label('Confirm Payment')
                    ->color('success')
                    ->visible(fn (Expense $record): bool => $record->isProcessing())
                    ->schema([
                        TextInput::make('reference')
                            ->required(),
                        Select::make('payment_method_id')
                            ->label('Payment Method')
                            ->options(fn (Expense $record): array => UserPaymentMethod::query()
                                ->where('user_id', $record->user_id)
                                ->pluck('label', 'id')
                                ->toArray())
                            ->nullable(),
                        Textarea::make('notes')
                            ->nullable(),
                    ])
                    ->action(function (array $data, Expense $record): void {
                        app(ExpensePaymentService::class)->confirmPayment(
                            $record,
                            $data['reference'],
                            $data['payment_method_id'] ?? null,
                            $data['notes'] ?? null,
                        );
                        Notification::make()->title('Payment confirmed!')->success()->send();
                    }),
            ]);
    }
}
