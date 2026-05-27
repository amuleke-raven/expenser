<?php

namespace App\Filament\Staff\Resources;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentSource;
use App\Enums\PaymentStatus;
use App\Enums\RecipientStatus;
use App\Filament\Staff\Resources\PendingPaymentResource\Pages;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\PendingPayment;
use App\Models\RewardRecipient;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class PendingPaymentResource extends Resource
{
    protected static ?string $model = PendingPayment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $modelLabel = 'Pending Payment';

    protected static ?string $pluralModelLabel = 'Pending Payments';

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_finance');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Pending Payment Details')
                ->schema([])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->checkIfRecordIsSelectableUsing(fn (PendingPayment $record): bool => in_array($record->status, [PaymentStatus::Pending, PaymentStatus::Processing]))
            ->modifyQueryUsing(fn ($query) => $query->with([
                'recipientUser',
                'rewardRecipient.user',
                'payable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    Expense::class => ['user', 'expenseType'],
                    RewardRecipient::class => ['reward.initiatedBy', 'reward.rewardType'],
                ]),
            ]))
            ->columns([
                TextColumn::make('payable_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        Expense::class => 'Expense',
                        RewardRecipient::class => 'Disbursement',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        Expense::class => 'info',
                        RewardRecipient::class => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('ref')
                    ->label('Ref')
                    ->getStateUsing(fn (PendingPayment $record): string => match (true) {
                        $record->payable instanceof Expense => $record->payable->ref(),
                        $record->payable instanceof RewardRecipient => $record->payable->reward?->ref() ?? '—',
                        default => '—',
                    }),

                TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->getStateUsing(function (PendingPayment $record): string {
                        if ($record->payment_source === PaymentSource::Expense) {
                            return $record->recipientUser?->name ?? '—';
                        }

                        return $record->rewardRecipient?->user?->name
                            ?? $record->rewardRecipient?->name
                            ?? '—';
                    }),

                TextColumn::make('recipient_email')
                    ->label('Email')
                    ->getStateUsing(function (PendingPayment $record): string {
                        if ($record->payment_source === PaymentSource::Expense) {
                            return $record->recipientUser?->email ?? '—';
                        }

                        return $record->rewardRecipient?->user?->email
                            ?? $record->rewardRecipient?->email
                            ?? '—';
                    }),
                TextColumn::make('requester_name')
                    ->label('Requester')
                    ->getStateUsing(function (PendingPayment $record): string {
                        if ($record->payable instanceof Expense) {
                            return $record->payable->user?->name ?? '—';
                        }

                        if ($record->payable instanceof RewardRecipient) {
                            return $record->payable->reward?->initiatedBy?->name ?? '—';
                        }

                        return '—';
                    }),

                TextColumn::make('disbursement_type')
                    ->label('Disbursement Type')
                    ->getStateUsing(function (PendingPayment $record): string {
                        if ($record->payable instanceof Expense) {
                            return $record->payable->expenseType?->name ?? '—';
                        }

                        if ($record->payable instanceof RewardRecipient) {
                            return $record->payable->reward?->rewardType?->name ?? '—';
                        }

                        return '—';
                    }),

                TextColumn::make('date_requested')
                    ->label('Date Requested')
                    ->getStateUsing(function (PendingPayment $record): string {
                        if ($record->payable instanceof Expense) {
                            $date = $record->payable->submitted_at ?? $record->payable->created_at;

                            return $date?->format('M j, Y') ?? '—';
                        }

                        if ($record->payable instanceof RewardRecipient) {
                            return $record->payable->reward?->created_at?->format('M j, Y') ?? '—';
                        }

                        return '—';
                    }),

                TextColumn::make('amount')->money(),
                TextColumn::make('currency.code')->label('Currency'),

                TextColumn::make('amount_usd')
                    ->label('Amount (USD)')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(fn (PendingPayment $record): string => '$'.number_format(
                        (float) $record->amount / max((float) ($record->currency?->conversion_rate ?? 1), 0.000001),
                        2
                    )
                    ),

                TextColumn::make('paymentMethod.name')->label('Payment Method'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => $state->color()),

                TextColumn::make('processedBy.name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Processed By'),
                TextColumn::make('processed_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->label('Processed At'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    )),

                SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::query()->orderBy('code')->pluck('code', 'id')),

                SelectFilter::make('payable_type')
                    ->label('Type')
                    ->options([
                        Expense::class => 'Expense',
                        RewardRecipient::class => 'Disbursement Recipient',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
                    ),
            ])
            ->actions([
                Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PendingPayment $record): bool => in_array($record->status, [PaymentStatus::Pending, PaymentStatus::Processing])
                    )
                    ->requiresConfirmation()
                    ->action(function (PendingPayment $record) {
                        $record->update([
                            'status' => PaymentStatus::Paid,
                            'processed_by' => auth()->id(),
                            'processed_at' => now(),
                        ]);

                        if ($record->payable instanceof Expense) {
                            $record->payable->update(['status' => ExpenseStatus::Paid]);
                        }

                        if ($record->payable instanceof RewardRecipient) {
                            $record->payable->update([
                                'status' => RecipientStatus::Paid,
                                'paid_at' => now(),
                            ]);
                        }
                    }),

                Action::make('mark_failed')
                    ->label('Mark as Failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PendingPayment $record): bool => in_array($record->status, [PaymentStatus::Pending, PaymentStatus::Processing]))
                    ->form([
                        Textarea::make('notes')->nullable()->label('Notes'),
                    ])
                    ->action(function (PendingPayment $record, array $data) {
                        $record->update([
                            'status' => PaymentStatus::Failed,
                            'notes' => $data['notes'],
                        ]);
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulk_mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $records
                            ->filter(fn (PendingPayment $record) => in_array($record->status, [PaymentStatus::Pending, PaymentStatus::Processing]))
                            ->each(function (PendingPayment $record) {
                                $record->update([
                                    'status' => PaymentStatus::Paid,
                                    'processed_by' => auth()->id(),
                                    'processed_at' => now(),
                                ]);

                                $record->load('payable');

                                if ($record->payable instanceof Expense) {
                                    $record->payable->update(['status' => ExpenseStatus::Paid]);
                                }

                                if ($record->payable instanceof RewardRecipient) {
                                    $record->payable->update([
                                        'status' => RecipientStatus::Paid,
                                        'paid_at' => now(),
                                    ]);
                                }
                            });
                    }),

                BulkAction::make('bulk_mark_failed')
                    ->label('Mark as Failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('notes')->nullable()->label('Failure Notes'),
                    ])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data) {
                        $records
                            ->filter(fn (PendingPayment $record) => in_array($record->status, [PaymentStatus::Pending, PaymentStatus::Processing]))
                            ->each(fn (PendingPayment $record) => $record->update([
                                'status' => PaymentStatus::Failed,
                                'notes' => $data['notes'],
                            ]));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendingPayments::route('/'),
        ];
    }
}
