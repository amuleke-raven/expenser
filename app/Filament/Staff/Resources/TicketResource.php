<?php

namespace App\Filament\Staff\Resources;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Staff\Resources\TicketResource\Pages;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketActivityLogger;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|\UnitEnum|null $navigationGroup = 'IT Support';

    protected static ?string $modelLabel = 'Support Ticket';

    protected static ?string $pluralModelLabel = 'My Tickets';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forRequester(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make('Submit a Support Ticket')->schema([
                TextInput::make('title')
                    ->required()
                    ->minLength(10)
                    ->maxLength(255)
                    ->helperText('Briefly describe your issue. Be as specific as possible (minimum 10 characters).'),

                Select::make('category_id')
                    ->label('Category')
                    ->options(fn () => TicketCategory::query()
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable()
                    ->helperText('Choose the category that best matches your issue.'),

                Select::make('priority')
                    ->options(collect(TicketPriority::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    ))
                    ->default(TicketPriority::Medium->value)
                    ->required()
                    ->helperText('Suggest a priority. IT staff may adjust this based on workload. Critical = system down, High = severely impacting work, Medium = standard issue, Low = minor inconvenience.'),

                RichEditor::make('description')
                    ->required()
                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])
                    ->columnSpanFull()
                    ->helperText('Describe your issue in detail. Include steps to reproduce, error messages, and screenshots if possible.'),

                FileUpload::make('attachment_files')
                    ->label('Attachments')
                    ->multiple()
                    ->maxFiles(10)
                    ->maxSize(10240)
                    ->acceptedFileTypes(['image/*', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'])
                    ->visibility('public')
                    ->disk('public')
                    ->directory('ticket-attachments')
                    ->columnSpanFull()
                    ->helperText('Attach screenshots, documents, or files. Max 10MB each. Accepted: images, PDF, Word, ZIP.'),
            ])->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Ticket Details')->schema([
                TextEntry::make('ticket_number')->label('Ticket #'),
                TextEntry::make('title'),
                TextEntry::make('category.name')->label('Category'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (TicketStatus $state): string => $state->color()),
                TextEntry::make('priority')
                    ->badge()
                    ->color(fn (TicketPriority $state): string => $state->color()),
                TextEntry::make('assignee.name')->label('Assigned To'),
                TextEntry::make('due_at')->dateTime()->label('SLA Due'),
                TextEntry::make('created_at')->dateTime()->label('Submitted'),
                TextEntry::make('description')->html()->columnSpanFull(),
            ])->columns(2),
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
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->since()
                    ->label('Submitted')
                    ->sortable(),

                TextColumn::make('sla_countdown_hours')
                    ->label('SLA')
                    ->badge()
                    ->color(fn (Ticket $record): string => match (true) {
                        $record->sla_breached || $record->sla_countdown_hours < 1 => 'danger',
                        $record->sla_countdown_hours < 4 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (Ticket $record): string => $record->sla_countdown_hours.'h'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options(collect(TicketStatus::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    )),

                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                SelectFilter::make('priority')
                    ->multiple()
                    ->options(collect(TicketPriority::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    )),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
                    ),
            ])
            ->actions([
                Action::make('add_reply')
                    ->label('Add Reply')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('primary')
                    ->form([
                        Textarea::make('body')
                            ->label('Your Reply')
                            ->required()
                            ->rows(4)
                            ->helperText('Write your reply to IT support.'),
                    ])
                    ->action(function (Ticket $record, array $data) {
                        $comment = $record->comments()->create([
                            'user_id' => auth()->id(),
                            'body' => $data['body'],
                            'is_internal' => false,
                        ]);

                        app()->make(TicketActivityLogger::class)->log($record, 'comment_added', [
                            'comment_id' => $comment->id,
                            'is_internal' => false,
                        ]);
                    }),

                Action::make('reopen')
                    ->label('Reopen')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Ticket $record): bool => $record->status === TicketStatus::Resolved
                        && $record->resolved_at !== null
                        && $record->resolved_at->gt(now()->subHours(48))
                    )
                    ->action(function (Ticket $record) {
                        $record->update(['status' => TicketStatus::Open]);
                        app()->make(TicketActivityLogger::class)->log($record, 'reopened');
                    }),

                Action::make('cancel')
                    ->label('Cancel Ticket')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Ticket $record): bool => in_array($record->status, [TicketStatus::Draft, TicketStatus::Open]))
                    ->action(fn (Ticket $record) => $record->update(['status' => TicketStatus::Cancelled])),

                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}
