<?php

namespace App\Filament\Admin\Resources\RewardResource\Pages;

use App\Filament\Admin\Resources\RewardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReward extends CreateRecord
{
    protected static string $resource = RewardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['initiated_by'] = auth()->id();

        return $data;
    }
}
