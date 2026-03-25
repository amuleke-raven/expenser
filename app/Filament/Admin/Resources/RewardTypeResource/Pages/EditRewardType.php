<?php

namespace App\Filament\Admin\Resources\RewardTypeResource\Pages;

use App\Filament\Admin\Resources\RewardTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRewardType extends EditRecord
{
    protected static string $resource = RewardTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
