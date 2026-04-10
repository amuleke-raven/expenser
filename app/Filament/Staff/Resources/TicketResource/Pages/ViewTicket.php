<?php

namespace App\Filament\Staff\Resources\TicketResource\Pages;

use App\Enums\TicketStatus;
use App\Filament\Staff\Resources\TicketResource;
use App\Services\TicketActivityLogger;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_reply')
                ->label('Add Reply')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('primary')
                ->form([
                    Textarea::make('body')
                        ->label('Your Reply')
                        ->required()
                        ->rows(4)
                        ->helperText('Write your update or reply to IT Support.'),
                ])
                ->action(function (array $data) {
                    $ticket = $this->record;
                    $comment = $ticket->comments()->create([
                        'user_id' => auth()->id(),
                        'body' => $data['body'],
                        'is_internal' => false,
                    ]);

                    app()->make(TicketActivityLogger::class)->log($ticket, 'comment_added', [
                        'comment_id' => $comment->id,
                        'is_internal' => false,
                    ]);
                }),

            Action::make('reopen')
                ->label('Reopen Ticket')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === TicketStatus::Resolved
                    && $this->record->resolved_at !== null
                    && $this->record->resolved_at->gt(now()->subHours(48))
                )
                ->action(function () {
                    $this->record->update(['status' => TicketStatus::Open]);
                    app()->make(TicketActivityLogger::class)->log($this->record, 'reopened');
                }),

            Action::make('cancel')
                ->label('Cancel Ticket')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status, [TicketStatus::Draft, TicketStatus::Open]))
                ->action(fn () => $this->record->update(['status' => TicketStatus::Cancelled])),
        ];
    }
}
