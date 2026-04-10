<?php

namespace App\Filament\It\Resources\TicketResource\Pages;

use App\Filament\It\Pages\TicketKanbanPage;
use App\Filament\It\Resources\TicketResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Kanban Board')
                ->icon('heroicon-o-view-columns')
                ->url(fn () => TicketKanbanPage::getUrl()),
        ];
    }
}
