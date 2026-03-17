<?php

namespace App\Filament\Admin\Resources\Rules\Pages;

use App\Filament\Admin\Resources\Rules\RuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRule extends EditRecord
{
    protected static string $resource = RuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
