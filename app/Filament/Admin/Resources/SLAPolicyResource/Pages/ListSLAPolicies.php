<?php

namespace App\Filament\Admin\Resources\SLAPolicyResource\Pages;

use App\Filament\Admin\Resources\SLAPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSLAPolicies extends ListRecords
{
    protected static string $resource = SLAPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
