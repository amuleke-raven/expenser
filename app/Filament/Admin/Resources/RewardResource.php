<?php

namespace App\Filament\Admin\Resources;

use App\Enums\RecipientType;
use App\Enums\RewardStatus;
use App\Enums\StepActionStatus;
use App\Events\RewardApproved;
use App\Events\RewardInitiated;
use App\Filament\Admin\Resources\RewardResource\Pages;
use App\Filament\Admin\Resources\RewardResource\RelationManagers\RewardRecipientsRelationManager;
use App\Models\Currency;
use App\Models\Project;
use App\Models\Reward;
use App\Models\RewardRecipient;
use App\Models\RewardType;
use App\Models\User;
use App\Services\WorkflowEngine;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RewardResource extends Resource
{
    protected static ?string $model = Reward::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|\UnitEnum|null $navigationGroup = 'Rewards';

    protected static ?string $modelLabel = 'Reward';

    protected static ?string $pluralModelLabel = 'Rewards';

    protected static ?string $recordTitleAttribute = 'ref';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Reward Details')
                ->schema([
                    Select::make('reward_type_id')
                        ->label('Reward Type')
                        ->options(RewardType::query()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $type = RewardType::find($state);
                                if ($type?->is_fixed) {
                                    if ($type->fixed_amount) {
                                        $set('amount', $type->fixed_amount);
                                    }
                                    if ($type->fixed_currency_id) {
                                        $set('currency_id', $type->fixed_currency_id);
                                    }
                                }
                            }
                        }),

                    TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->disabledOn('edit'),

                    Select::make('currency_id')
                        ->label('Currency')
                        ->options(Currency::query()->pluck('code', 'id'))
                        ->default(fn () => Currency::where('code', 'USD')->first()?->id)
                        ->required()
                        ->searchable()
                        ->disabled(fn (Get $get): bool => (bool) RewardType::find($get('reward_type_id'))?->is_fixed)
                        ->dehydrated(),

                    Select::make('project_id')
                        ->label('Project')
                        ->options(Project::query()->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),

                    Select::make('recipient_type')
                        ->label('Recipient Type')
                        ->options(collect(RecipientType::cases())->mapWithKeys(
                            fn ($case) => [$case->value => $case->label()]
                        ))
                        ->default(RecipientType::Internal->value)
                        ->required(),

                    DatePicker::make('payout_date')
                        ->label('Payout Date')
                        ->nullable(),

                    Textarea::make('notes')->nullable()->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ref')->label('Ref'),
                TextColumn::make('rewardType.name')->label('Type'),
                TextColumn::make('initiatedBy.name')->label('Initiated By'),
                TextColumn::make('amount')->money(),
                TextColumn::make('currency.code')->label('Currency'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (RewardStatus $state): string => $state->color()),
                TextColumn::make('project.name')->label('Project'),
                TextColumn::make('payout_date')
                    ->label('Payout Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(RewardStatus::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    )),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (Reward $record): bool {
                        if ($record->status !== RewardStatus::PendingApproval) {
                            return false;
                        }

                        return self::userCanApproveReward($record);
                    })
                    ->action(function (Reward $record, $livewire) {
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
                    ->visible(function (Reward $record): bool {
                        if ($record->status !== RewardStatus::PendingApproval) {
                            return false;
                        }

                        return self::userCanApproveReward($record);
                    })
                    ->form([
                        Textarea::make('rejection_reason')
                            ->required()
                            ->label('Rejection Reason'),
                    ])
                    ->action(function (Reward $record, array $data, $livewire) {
                        $mhw = $record->modelHasWorkflow()->with('workflow.steps')->first();
                        $action = $mhw?->stepActions()->where('status', StepActionStatus::Pending)->first();

                        if ($action) {
                            $record->update(['rejection_reason' => $data['rejection_reason']]);
                            app(WorkflowEngine::class)->advance($action, StepActionStatus::Rejected, $data['rejection_reason'], auth()->user());
                        }

                        $livewire->resetTable();
                    }),

                Action::make('add_recipients')
                    ->label('Add Recipients')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (Reward $record): bool => ! in_array($record->status, [RewardStatus::Approved, RewardStatus::Rejected]))
                    ->form(fn (Reward $record) => $record->recipient_type === RecipientType::External
                        ? [
                            Repeater::make('recipients')
                                ->label('External Recipients')
                                ->schema([
                                    TextInput::make('name')->required()->label('Name'),
                                    TextInput::make('email')->email()->required()->label('Email'),
                                ])
                                ->minItems(1)
                                ->addActionLabel('Add Recipient'),
                        ]
                        : [
                            Select::make('user_ids')
                                ->label('Select Recipients')
                                ->options(User::query()->pluck('name', 'id'))
                                ->multiple()
                                ->searchable()
                                ->required(),
                        ]
                    )
                    ->action(function (Reward $record, array $data) {
                        if ($record->recipient_type === RecipientType::External) {
                            foreach ($data['recipients'] as $recipient) {
                                RewardRecipient::firstOrCreate(
                                    ['reward_id' => $record->id, 'email' => $recipient['email']],
                                    ['name' => $recipient['name']],
                                );
                            }
                        } else {
                            foreach ($data['user_ids'] as $userId) {
                                RewardRecipient::firstOrCreate([
                                    'reward_id' => $record->id,
                                    'user_id' => $userId,
                                ]);
                            }
                        }
                    }),

                Action::make('submit_for_approval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Reward $record): bool => $record->status === RewardStatus::Draft)
                    ->requiresConfirmation()
                    ->action(function (Reward $record) {
                        $record->update(['status' => RewardStatus::PendingApproval]);
                        event(new RewardInitiated($record));

                        $type = $record->rewardType;
                        if ($type->requires_approval && $type->workflow_id) {
                            app(WorkflowEngine::class)->initiate($record, $type->workflow);
                        } else {
                            $record->update(['status' => RewardStatus::Approved]);
                            event(new RewardApproved($record));
                        }
                    }),
            ]);
    }

    protected static function userCanApproveReward(Reward $record): bool
    {
        return $record->modelHasWorkflow !== null
            && auth()->user()->can('approve_rewards');
    }

    public static function getRelations(): array
    {
        return [
            RewardRecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRewards::route('/'),
            'create' => Pages\CreateReward::route('/create'),
            'edit' => Pages\EditReward::route('/{record}/edit'),
        ];
    }
}
