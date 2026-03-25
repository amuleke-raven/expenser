<?php

namespace App\Filament\Staff\Resources;

use App\Enums\ExpenseStatus;
use App\Filament\Staff\Resources\ExpenseResource\Pages;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Services\ExpenseRuleEngine;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'My Expense';

    protected static ?string $pluralModelLabel = 'My Expenses';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forUser(auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('expense_type_id')
                ->label('Expense Type')
                ->options(
                    ExpenseType::query()
                        ->whereHas('expenseGroup.roles', fn ($q) => $q->whereIn('id', auth()->user()->roles->pluck('id'))
                        )
                        ->with('expenseGroup')
                        ->get()
                        ->mapWithKeys(fn ($type) => [$type->id => "{$type->expenseGroup->name} — {$type->name}"])
                )
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    // Reset attachment requirement when type changes
                }),

            Select::make('project_id')
                ->label('Project')
                ->options(fn () => auth()->user()->projects()->pluck('name', 'projects.id'))
                ->searchable()
                ->nullable(),

            Select::make('currency_id')
                ->label('Currency')
                ->options(Currency::query()->pluck('code', 'id'))
                ->default(fn () => auth()->user()->currency_id)
                ->required()
                ->searchable(),

            Textarea::make('description')
                ->nullable()
                ->columnSpanFull(),

            Repeater::make('lineItems')
                ->relationship('lineItems')
                ->label('Line Items')
                ->schema([
                    TextInput::make('description')->required()->maxLength(255),

                    TextInput::make('quantity')
                        ->numeric()
                        ->default(1)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => $set('total', (float) $get('quantity') * (float) $get('unit_price'))
                        ),

                    TextInput::make('unit_price')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => $set('total', (float) $get('quantity') * (float) $get('unit_price'))
                        ),

                    TextInput::make('total')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->label('Total'),

                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->hidden(),
                ])
                ->minItems(1)
                ->addActionLabel('Add Line Item')
                ->columnSpanFull(),

            FileUpload::make('attachment_files')
                ->label('Attachments')
                ->multiple()
                ->acceptedFileTypes(config('remoteraven.supported_attachment_mimes'))
                ->maxSize(config('remoteraven.max_attachment_size_mb') * 1024)
                ->visibility('public')
                ->disk('public')
                ->directory('expense-attachments')
                ->visible(fn (Get $get): bool => (bool) ExpenseType::find($get('expense_type_id'))?->requires_attachment
                )
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ref')
                    ->label('Ref')
                    ->getStateUsing(fn (Expense $record): string => $record->ref()),

                TextColumn::make('expenseType.name')->label('Type'),
                TextColumn::make('project.name')->label('Project'),
                TextColumn::make('total_amount')->money(),
                TextColumn::make('currency.code')->label('Currency'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ExpenseStatus $state): string => $state->color()),

                TextColumn::make('submitted_at')->dateTime()->label('Submitted'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(ExpenseStatus::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    )),

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
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Expense $record): bool => $record->status === ExpenseStatus::Draft)
                    ->requiresConfirmation()
                    ->modalHeading('Submit this expense for approval?')
                    ->action(function (Expense $record) {
                        $result = app(ExpenseRuleEngine::class)->evaluate($record);

                        if (! $result->passes) {
                            $messages = collect($result->failedRules)
                                ->map(fn ($rule) => "Rule failed: {$rule->dimension->label()} {$rule->operator->label()}")
                                ->join(', ');

                            Notification::make()
                                ->title('Cannot Submit')
                                ->body($messages)
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['status' => ExpenseStatus::Submitted]);

                        Notification::make()
                            ->title('Expense submitted successfully')
                            ->success()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Expense $record): bool => $record->status === ExpenseStatus::Draft),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
            'view' => Pages\ViewExpense::route('/{record}'),
        ];
    }
}
