<?php

namespace App\Filament\Admin\Resources\RewardResource\Pages;

use App\Enums\RewardStatus;
use App\Filament\Admin\Resources\RewardResource;
use App\Models\Reward;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReward extends ViewRecord
{
    protected static string $resource = RewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Reward $record): bool => $record->status === RewardStatus::Draft),
        ];
    }
}
