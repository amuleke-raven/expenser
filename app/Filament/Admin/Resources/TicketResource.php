<?php

namespace App\Filament\Admin\Resources;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\TicketResource\Pages;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketActivityLogger;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|\UnitEnum|null $navigationGroup = 'IT Support';

    protected static ?string $modelLabel = 'Ticket';

    protected static ?string $pluralModelLabel = 'Tickets';

    protected static ?string $recordTitleAttribute = 'ticket_number';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Ticket Details')->schema([
                Section::make('')->schema([
                    TextEntry::make('ticket_number')->label('Ticket #'),
                    TextEntry::make('title'),
                    TextEntry::make('requester.name')->label('Requester'),
                    TextEntry::make('category.name')->label('Category'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (TicketStatus $state): string => $state->color()),
                    TextEntry::make('priority')
                        ->badge()
                        ->color(fn (TicketPriority $state): string => $state->color()),
                    TextEntry::make('assignee.name')->label('Assignee'),
                    TextEntry::make('due_at')->dateTime()->label('Due At'),
                    TextEntry::make('created_at')->dateTime()->label('Created'),
                    TextEntry::make('description')->html()->columnSpanFull(),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('category_id')
                    ->label('Category')
                    ->options(fn () => TicketCategory::query()->where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Select::make('requester_id')
                    ->label('Requester')
                    ->options(User::query()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Select::make('status')
                    ->options(collect(TicketStatus::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    ))
                    ->required()
                    ->default(TicketStatus::Open->value),

                Select::make('priority')
                    ->options(collect(TicketPriority::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    ))
                    ->required()
                    ->default(TicketPriority::Medium->value),

                Select::make('assignee_id')
                    ->label('Assignee')
                    ->options(User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),

                RichEditor::make('description')
                    ->required()
                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])
                    ->columnSpanFull(),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ticket_number')
                    ->label('Ticket #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('requester.name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (TicketStatus $state): string => $state->color())
                    ->sortable(),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (TicketPriority $state): string => $state->color())
                    ->sortable(),

                TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('due_at')
                    ->dateTime()
                    ->label('Due')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->since()
                    ->label('Created')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options(collect(TicketStatus::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    )),

                SelectFilter::make('priority')
                    ->multiple()
                    ->options(collect(TicketPriority::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    )),

                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                Filter::make('sla_breached')
                    ->label('SLA Breached')
                    ->query(fn ($query) => $query->where('sla_breached', true)),
            ])
            ->actions([
                Action::make('reassign')
                    ->label('Reassign')
                    ->icon('heroicon-o-user')
                    ->color('warning')
                    ->form([
                        Select::make('assignee_id')
                            ->label('Assign To')
                            ->options(User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Ticket $record, array $data) {
                        $record->update(['assignee_id' => $data['assignee_id']]);
                    }),

                Action::make('force_close')
                    ->label('Force Close')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Ticket $record) {
                        $record->update(['status' => TicketStatus::Closed]);
                        app()->make(TicketActivityLogger::class)->log($record, 'force_closed');
                    }),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('force_close_bulk')
                    ->label('Force Close Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $logger = app()->make(TicketActivityLogger::class);
                        foreach ($records as $record) {
                            $record->update(['status' => TicketStatus::Closed]);
                            $logger->log($record, 'force_closed');
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}
