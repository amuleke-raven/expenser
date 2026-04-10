<?php

namespace App\Filament\It\Resources\TicketResource\Pages;

use App\Filament\It\Resources\TicketResource;
use App\Notifications\Tickets\TicketCommentAddedNotification;
use App\Services\TicketActivityLogger;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_comment')
                ->label('Add Comment')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->form([
                    Textarea::make('body')
                        ->label('Comment')
                        ->required()
                        ->rows(4)
                        ->helperText('Write your reply or update.'),

                    Toggle::make('is_internal')
                        ->label('Internal Note')
                        ->helperText('Internal notes are only visible to IT staff.'),
                ])
                ->action(function (array $data) {
                    $ticket = $this->record;
                    $comment = $ticket->comments()->create([
                        'user_id' => auth()->id(),
                        'body' => $data['body'],
                        'is_internal' => $data['is_internal'] ?? false,
                    ]);

                    app()->make(TicketActivityLogger::class)->log($ticket, 'comment_added', [
                        'comment_id' => $comment->id,
                        'is_internal' => $comment->is_internal,
                    ]);

                    if (! $comment->is_internal) {
                        $ticket->requester->notify(
                            new TicketCommentAddedNotification($ticket, $comment)
                        );
                    }
                }),

            EditAction::make(),
        ];
    }
}
