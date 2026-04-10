<?php

namespace App\Filament\It\Resources;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\It\Resources\TicketResource\Pages;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketActivityLogger;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

    protected static ?string $pluralModelLabel = 'Support Queue';

    protected static ?string $recordTitleAttribute = 'ticket_number';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Ticket Details')->schema([
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
                TextEntry::make('sla_countdown_hours')->label('SLA Countdown (hrs)'),
                TextEntry::make('created_at')->dateTime()->label('Created'),
                TextEntry::make('description')->html()->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Update Ticket')->schema([
                Select::make('status')
                    ->options(fn (?Ticket $record) => $record
                        ? collect($record->status->allowedTransitionsFor('it_staff'))->mapWithKeys(
                            fn (TicketStatus $s) => [$s->value => $s->label()]
                        )->prepend($record->status->label(), $record->status->value)
                        : collect(TicketStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                    )
                    ->required()
                    ->helperText('Select the new status for this ticket.'),

                Select::make('priority')
                    ->options(collect(TicketPriority::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    ))
                    ->required()
                    ->helperText('Adjust priority as needed.'),

                Select::make('assignee_id')
                    ->label('Assignee')
                    ->options(fn () => User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['it_staff', 'admin', 'super_admin']))->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->helperText('Assign to an IT staff member.'),

                Textarea::make('internal_note')
                    ->label('Internal Note (optional)')
                    ->helperText('This note is visible to IT staff only, not the requester.')
                    ->rows(3)
                    ->dehydrated(false),
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
                    ->limit(35),

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
                    ->label('Opened')
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

                Filter::make('unassigned')
                    ->label('Unassigned')
                    ->query(fn ($query) => $query->whereNull('assignee_id')),

                Filter::make('sla_breached')
                    ->label('SLA Breached')
                    ->query(fn ($query) => $query->where('sla_breached', true)),
            ])
            ->actions([
                Action::make('quick_update')
                    ->label('Quick Update')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form(fn (Ticket $record) => [
                        Select::make('status')
                            ->options(
                                collect($record->status->allowedTransitionsFor('it_staff'))
                                    ->mapWithKeys(fn (TicketStatus $s) => [$s->value => $s->label()])
                                    ->prepend($record->status->label(), $record->status->value)
                            )
                            ->default($record->status->value)
                            ->required()
                            ->helperText('Select new status.'),

                        Textarea::make('internal_note')
                            ->label('Internal Note')
                            ->helperText('Optional note visible to IT staff only.')
                            ->rows(3),
                    ])
                    ->action(function (Ticket $record, array $data) {
                        $record->update(['status' => $data['status']]);

                        if (! empty($data['internal_note'])) {
                            $comment = $record->comments()->create([
                                'user_id' => auth()->id(),
                                'body' => $data['internal_note'],
                                'is_internal' => true,
                            ]);
                            app()->make(TicketActivityLogger::class)->log($record, 'comment_added', [
                                'comment_id' => $comment->id,
                                'is_internal' => true,
                            ]);
                        }
                    }),

                Action::make('assign_to_me')
                    ->label('Assign to Me')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->action(fn (Ticket $record) => $record->update(['assignee_id' => auth()->id()])),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('assign_to_me_bulk')
                    ->label('Assign to Me')
                    ->icon('heroicon-o-user-plus')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $record->update(['assignee_id' => auth()->id()]);
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}
