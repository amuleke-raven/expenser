<?php

namespace App\Filament\Staff\Resources;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentSource;
use App\Enums\PaymentStatus;
use App\Enums\RecipientStatus;
use App\Enums\RewardStatus;
use App\Filament\Staff\Resources\PendingPaymentResource\Pages;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\PendingPayment;
use App\Models\RewardRecipient;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing, PaymentStatus::Failed]);
    }

    public static function eagerLoadRelations(Builder $query): Builder
    {
        return $query->with([
            'recipientUser',
            'rewardRecipient.user',
            'payable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Expense::class => ['user', 'expenseType', 'project', 'currency', 'lineItems', 'attachments'],
                RewardRecipient::class => ['reward.initiatedBy', 'reward.rewardType', 'reward.project', 'reward.currency', 'reward.attachments', 'reward.recipients.user'],
            ]),
        ]);
    }

    /**
     * Columns shared between the Pending Payments and Processed Payments tables.
     *
     * @return array<int, TextColumn|IconColumn>
     */
    public static function baseColumns(): array
    {
        return [
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
                })
                ->sortable(query: function ($query, string $direction) {
                    $query
                        ->select('pending_payments.*')
                        ->leftJoin('users as expense_recipient_users', function ($join) {
                            $join->on('pending_payments.recipient_id', '=', 'expense_recipient_users.id')
                                ->where('pending_payments.payment_source', '=', PaymentSource::Expense->value);
                        })
                        ->leftJoin('reward_recipients as sorted_reward_recipients', function ($join) {
                            $join->on('pending_payments.recipient_id', '=', 'sorted_reward_recipients.id')
                                ->where('pending_payments.payment_source', '=', PaymentSource::Reward->value);
                        })
                        ->leftJoin('users as reward_recipient_users', 'sorted_reward_recipients.user_id', '=', 'reward_recipient_users.id')
                        ->orderByRaw(
                            "COALESCE(expense_recipient_users.name, reward_recipient_users.name, sorted_reward_recipients.name) {$direction}"
                        );
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

            IconColumn::make('is_billable')
                ->label('Billable')
                ->boolean()
                ->getStateUsing(function (PendingPayment $record): ?bool {
                    if ($record->payable instanceof Expense) {
                        return $record->payable->is_billable;
                    }

                    if ($record->payable instanceof RewardRecipient) {
                        return $record->payable->reward?->is_billable;
                    }

                    return null;
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

            TextColumn::make('amount')->numeric(decimalPlaces: 2),
            TextColumn::make('currency.code')->label('Currency'),

            TextColumn::make('amount_usd')
                ->label('Amount (USD)')
                ->toggleable(isToggledHiddenByDefault: true)
                ->getStateUsing(fn (PendingPayment $record): string => '$'.number_format(
                    (float) $record->amount / max((float) ($record->currency?->conversion_rate ?? 1), 0.000001),
                    2
                )
                ),

            TextColumn::make('payment_method_display')
                ->label('Payment Method')
                ->getStateUsing(fn (PendingPayment $record): string => $record->paymentMethod?->name
                    ?? $record->manual_payment_details
                    ?? '—'
                ),
        ];
    }

    /**
     * Detail view (slide-over infolist) shared between the Pending and Processed payment tables.
     *
     * @return array<int, Section>
     */
    public static function detailsInfolist(PendingPayment $record): array
    {
        if ($record->payable instanceof Expense) {
            $expense = $record->payable;

            return [
                Section::make('Expense Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('ref')
                            ->label('Reference')
                            ->getStateUsing(fn () => $expense->ref()),
                        TextEntry::make('expense_type')
                            ->label('Type')
                            ->getStateUsing(fn () => $expense->expenseType?->name ?? '—'),
                        TextEntry::make('project')
                            ->label('Project')
                            ->getStateUsing(fn () => $expense->project?->name ?? '—'),
                        TextEntry::make('submitted_by')
                            ->label('Submitted By')
                            ->getStateUsing(fn () => $expense->user?->name ?? '—'),
                        TextEntry::make('currency')
                            ->label('Currency')
                            ->getStateUsing(fn () => $expense->currency?->code ?? '—'),
                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->getStateUsing(fn () => $expense->total_amount)
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('status')
                            ->label('Status')
                            ->getStateUsing(fn () => $expense->status->label())
                            ->badge()
                            ->color(fn () => $expense->status->color()),
                        TextEntry::make('submitted_at')
                            ->label('Submitted At')
                            ->getStateUsing(fn () => $expense->submitted_at?->format('d M Y, H:i') ?? '—'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->getStateUsing(fn () => $expense->description ?? '—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Line Items')
                    ->schema([
                        RepeatableEntry::make('line_items')
                            ->hiddenLabel()
                            ->state(fn () => $expense->lineItems)
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
                    ->visible(fn () => $expense->attachments->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->state(fn () => $expense->attachments)
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

        if ($record->payable instanceof RewardRecipient) {
            $reward = $record->payable->reward;

            return [
                Section::make('Disbursement Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('ref')
                            ->label('Reference')
                            ->getStateUsing(fn () => $reward?->ref() ?? '—'),
                        TextEntry::make('reward_type')
                            ->label('Reward Type')
                            ->getStateUsing(fn () => $reward?->rewardType?->name ?? '—'),
                        TextEntry::make('project')
                            ->label('Project')
                            ->getStateUsing(fn () => $reward?->project?->name ?? '—'),
                        TextEntry::make('initiated_by')
                            ->label('Initiated By')
                            ->getStateUsing(fn () => $reward?->initiatedBy?->name ?? '—'),
                        TextEntry::make('currency')
                            ->label('Currency')
                            ->getStateUsing(fn () => $reward?->currency?->code ?? '—'),
                        TextEntry::make('amount')
                            ->label('Amount')
                            ->getStateUsing(fn () => $reward?->amount)
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('status')
                            ->label('Status')
                            ->getStateUsing(fn () => $reward?->status->label() ?? '—')
                            ->badge()
                            ->color(fn () => $reward?->status instanceof RewardStatus ? $reward->status->color() : 'gray'),
                        TextEntry::make('payout_date')
                            ->label('Payout Date')
                            ->getStateUsing(fn () => $reward?->payout_date?->format('d M Y') ?? '—'),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->getStateUsing(fn () => $reward?->notes ?? '—')
                            ->columnSpanFull(),
                    ]),
                Section::make('This Recipient')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('recipient_name')
                            ->label('Name')
                            ->getStateUsing(fn () => $record->payable->user?->name ?? $record->payable->name ?? '—'),
                        TextEntry::make('recipient_email')
                            ->label('Email')
                            ->getStateUsing(fn () => $record->payable->user?->email ?? $record->payable->email ?? '—'),
                        TextEntry::make('recipient_status')
                            ->label('Status')
                            ->getStateUsing(fn () => $record->payable->status->label())
                            ->badge()
                            ->color(fn () => $record->payable->status->color()),
                    ]),
                Section::make('All Recipients')
                    ->visible(fn () => $reward?->recipients->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('recipients')
                            ->hiddenLabel()
                            ->state(fn () => $reward?->recipients ?? collect())
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
                    ->visible(fn () => $reward?->attachments->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->state(fn () => $reward?->attachments ?? collect())
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
            ->currentSelectionLivewireProperty('selectedTableRecordIds')
            ->checkIfRecordIsSelectableUsing(fn (PendingPayment $record): bool => in_array($record->status, [PaymentStatus::Pending, PaymentStatus::Processing]))
            ->modifyQueryUsing(fn (Builder $query) => self::eagerLoadRelations($query))
            ->recordAction('view_details')
            ->columns([
                ...self::baseColumns(),

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
                    ->options(collect(PaymentStatus::cases())
                        ->reject(fn (PaymentStatus $case) => $case === PaymentStatus::Paid)
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                    ),

                SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::query()->orderBy('code')->pluck('code', 'id')),

                SelectFilter::make('payable_type')
                    ->label('Type')
                    ->options([
                        Expense::class => 'Expense',
                        RewardRecipient::class => 'Disbursement Recipient',
                    ]),

                SelectFilter::make('recipient')
                    ->label('Recipient')
                    ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->query(function ($query, array $data) {
                        $userId = $data['value'] ?? null;

                        if (! $userId) {
                            return;
                        }

                        $query->where(function ($q) use ($userId) {
                            $q->where(function ($inner) use ($userId) {
                                $inner->where('payment_source', PaymentSource::Expense)
                                    ->where('recipient_id', $userId);
                            })->orWhere(function ($inner) use ($userId) {
                                $inner->where('payment_source', PaymentSource::Reward)
                                    ->whereIn('recipient_id', RewardRecipient::query()
                                        ->where('user_id', $userId)
                                        ->select('id')
                                    );
                            });
                        });
                    }),

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
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->infolist(fn (PendingPayment $record): array => self::detailsInfolist($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver(),

                Action::make('edit_payment_method')
                    ->label('Edit Payment Method')
                    ->icon('heroicon-o-credit-card')
                    ->color('gray')
                    ->visible(fn (PendingPayment $record): bool => in_array($record->status, [PaymentStatus::Pending, PaymentStatus::Processing]))
                    ->fillForm(fn (PendingPayment $record): array => [
                        'payment_method_id' => $record->payment_method_id,
                        'manual_payment_details' => $record->manual_payment_details,
                    ])
                    ->form([
                        Select::make('payment_method_id')
                            ->label('Payment Method')
                            ->placeholder('Select a payment method')
                            ->options(PaymentMethod::query()->orderBy('name')->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->live(),
                        TextInput::make('manual_payment_details')
                            ->label('Manual Payment Details')
                            ->placeholder('e.g. M-Pesa 0712345678 / Bank: KCB 1234567890')
                            ->helperText('Use this if the payment method is not in the list above.')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->action(function (PendingPayment $record, array $data) {
                        $record->update([
                            'payment_method_id' => $data['payment_method_id'],
                            'manual_payment_details' => $data['manual_payment_details'],
                        ]);
                    }),

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
