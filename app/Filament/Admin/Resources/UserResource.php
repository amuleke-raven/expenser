<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Tabs')
                ->tabs([
                    Tab::make('Details')
                        ->schema([
                            TextInput::make('name')->required()->maxLength(255),
                            TextInput::make('email')->email()->required()->maxLength(255),
                            TextInput::make('phone')->nullable()->maxLength(50),
                            TextInput::make('password')
                                ->password()
                                ->revealable()
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                ->dehydrated(fn ($state) => filled($state))
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->default(fn (string $operation): ?string => $operation === 'create' ? Str::password(8, letters: true, numbers: true, symbols: false) : null)
                                ->label(fn (string $operation): string => $operation === 'create' ? 'Password' : 'Password (leave blank to keep current)')
                                ->helperText('Auto-generated password. You can view or change it.')
                                ->suffixAction(
                                    Action::make('generatePassword')
                                        ->icon(Heroicon::ArrowPath)
                                        ->action(function (Set $set) {
                                            $set('password', Str::password(8, letters: true, numbers: true, symbols: false));
                                        })
                                        ->tooltip('Generate new password')
                                ),
                            Select::make('country_id')
                                ->label('Country')
                                ->options(Country::query()->pluck('name', 'id'))
                                ->searchable()
                                ->nullable(),
                            Select::make('currency_id')
                                ->label('Currency')
                                ->options(Currency::query()->pluck('code', 'id'))
                                ->searchable()
                                ->nullable(),
                        ]),

                    Tab::make('Roles')
                        ->schema([
                            CheckboxList::make('roles')
                                ->relationship('roles', 'name')
                                ->options(Role::query()->pluck('name', 'id'))
                                ->label('Assigned Roles'),
                        ]),

                    Tab::make('Projects')
                        ->schema([
                            CheckboxList::make('projects')
                                ->relationship('projects', 'name')
                                ->options(Project::query()->pluck('name', 'id'))
                                ->label('Assigned Projects'),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('country.name')->label('Country'),
                TextColumn::make('currency.code')->label('Currency'),
                TextColumn::make('roles.name')
                    ->badge()
                    ->label('Roles'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
