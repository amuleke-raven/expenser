<?php

namespace App\Filament\Admin\Resources\Rules\Pages;

use App\Filament\Admin\Resources\Rules\RuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRules extends ListRecords
{
    protected static string $resource = RuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
