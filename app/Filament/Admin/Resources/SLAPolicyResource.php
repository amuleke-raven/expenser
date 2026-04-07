<?php

namespace App\Filament\Admin\Resources;

use App\Enums\TicketPriority;
use App\Filament\Admin\Resources\SLAPolicyResource\Pages;
use App\Models\SlaPolicy;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SLAPolicyResource extends Resource
{
    protected static ?string $model = SlaPolicy::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'IT Support';

    protected static ?string $modelLabel = 'SLA Policy';

    protected static ?string $pluralModelLabel = 'SLA Policies';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('priority')
                ->options(collect(TicketPriority::cases())->mapWithKeys(
                    fn ($case) => [$case->value => $case->label()]
                ))
                ->required()
                ->disabled()
                ->helperText('Priority level cannot be changed.'),

            TextInput::make('response_hours')
                ->label('Response Hours')
                ->numeric()
                ->required()
                ->minValue(1)
                ->helperText('Maximum hours to first response.'),

            TextInput::make('resolve_hours')
                ->label('Resolve Hours')
                ->numeric()
                ->required()
                ->minValue(1)
                ->helperText('Maximum hours to resolve the ticket.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('priority')
                ->badge()
                ->color(fn (TicketPriority $state): string => $state->color()),
            TextEntry::make('response_hours')->label('Response Hours'),
            TextEntry::make('resolve_hours')->label('Resolve Hours'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (TicketPriority $state): string => $state->color())
                    ->sortable(),

                TextColumn::make('response_hours')
                    ->label('Response Hours')
                    ->sortable(),

                TextColumn::make('resolve_hours')
                    ->label('Resolve Hours')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSLAPolicies::route('/'),
            'create' => Pages\CreateSLAPolicy::route('/create'),
            'edit' => Pages\EditSLAPolicy::route('/{record}/edit'),
        ];
    }
}
