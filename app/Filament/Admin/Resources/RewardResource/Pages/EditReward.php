<?php

namespace App\Filament\Admin\Resources\RewardResource\Pages;

use App\Enums\RewardStatus;
use App\Filament\Admin\Resources\RewardResource;
use App\Models\Reward;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReward extends EditRecord
{
    protected static string $resource = RewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Reward $record): bool => $record->status === RewardStatus::Draft),
        ];
    }
}
