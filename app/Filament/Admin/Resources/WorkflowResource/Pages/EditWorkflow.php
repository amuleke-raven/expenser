<?php

namespace App\Filament\Admin\Resources\WorkflowResource\Pages;

use App\Filament\Admin\Resources\WorkflowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkflow extends EditRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
