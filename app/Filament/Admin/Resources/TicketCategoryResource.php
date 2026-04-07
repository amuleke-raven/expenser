<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TicketCategoryResource\Pages;
use App\Models\TicketCategory;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TicketCategoryResource extends Resource
{
    protected static ?string $model = TicketCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'IT Support';

    protected static ?string $modelLabel = 'Ticket Category';

    protected static ?string $pluralModelLabel = 'Ticket Categories';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state)))
                ->helperText('The display name for this category.'),

            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Auto-generated from name. Must be unique.'),

            TextInput::make('icon')
                ->maxLength(255)
                ->placeholder('computer-desktop')
                ->helperText('Heroicon name without prefix, e.g. "computer-desktop".'),

            TextInput::make('sla_hours')
                ->numeric()
                ->required()
                ->default(24)
                ->minValue(1)
                ->helperText('Default SLA hours for tickets in this category.'),

            Select::make('default_assignee_id')
                ->label('Default Assignee')
                ->options(fn () => User::query()->whereHas('roles', fn ($q) => $q->where('name', 'it_staff'))->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->helperText('Optional: automatically assign new tickets in this category.'),

            Toggle::make('is_active')
                ->default(true)
                ->helperText('Inactive categories are hidden from the ticket submission form.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('icon')
                    ->searchable(),

                TextColumn::make('sla_hours')
                    ->label('SLA Hours')
                    ->sortable(),

                TextColumn::make('defaultAssignee.name')
                    ->label('Default Assignee')
                    ->searchable(),

                TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->counts('tickets')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTicketCategories::route('/'),
            'create' => Pages\CreateTicketCategory::route('/create'),
            'edit' => Pages\EditTicketCategory::route('/{record}/edit'),
        ];
    }
}
