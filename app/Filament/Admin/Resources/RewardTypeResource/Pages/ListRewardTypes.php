<?php

namespace App\Filament\Admin\Resources\RewardTypeResource\Pages;

use App\Filament\Admin\Resources\RewardTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRewardTypes extends ListRecords
{
    protected static string $resource = RewardTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
