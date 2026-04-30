<?php

namespace App\Filament\Admin\Resources\RewardResource\RelationManagers;

use App\Enums\RewardStatus;
use App\Models\Reward;
use App\Models\RewardAttachment;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RewardAttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Attachments';

    public function isReadOnly(): bool
    {
        /** @var Reward $reward */
        $reward = $this->getOwnerRecord();

        return in_array($reward->status, [RewardStatus::Approved, RewardStatus::Rejected, RewardStatus::Paid]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            // No form needed - attachments are managed via FileUpload in main form
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (RewardAttachment $record) => \Storage::url($record->path), shouldOpenInNewTab: true),
            ])
            ->emptyStateHeading('No attachments')
            ->emptyStateDescription('Attachments will appear here once uploaded.')
            ->emptyStateIcon('heroicon-o-paper-clip');
    }
}
