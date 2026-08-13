<?php

namespace App\Filament\Staff\Resources;

use App\Enums\PaymentSource;
use App\Enums\PaymentStatus;
use App\Filament\Staff\Resources\ProcessedPaymentResource\Pages;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\PendingPayment;
use App\Models\RewardRecipient;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProcessedPaymentResource extends Resource
{
    protected static ?string $model = PendingPayment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $modelLabel = 'Processed Payment';

    protected static ?string $pluralModelLabel = 'Processed Payments';

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_finance');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('pending_payments.status', PaymentStatus::Paid);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('processed_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => PendingPaymentResource::eagerLoadRelations($query))
            ->recordAction('view_details')
            ->columns([
                ...PendingPaymentResource::baseColumns(),

                TextColumn::make('processedBy.name')
                    ->label('Processed By')
                    ->sortable(),
                TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
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

                Filter::make('processed_at')
                    ->form([
                        DatePicker::make('processed_from'),
                        DatePicker::make('processed_until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['processed_from'], fn ($q, $date) => $q->whereDate('processed_at', '>=', $date))
                        ->when($data['processed_until'], fn ($q, $date) => $q->whereDate('processed_at', '<=', $date))
                    ),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->infolist(fn (PendingPayment $record): array => PendingPaymentResource::detailsInfolist($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcessedPayments::route('/'),
        ];
    }
}
