<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Exports\BulkImportResultExport;
use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importUsers')
                ->label('Import via CSV')
                ->icon(Heroicon::ArrowUpTray)
                ->visible(fn (): bool => auth()->user()->hasRole('super_admin'))
                ->modalHeading('Bulk Import Users')
                ->modalDescription('Upload a CSV file with columns: name, email. Optional columns: phone, department_id, currency_id, country_id. Passwords will be auto-generated.')
                ->modalSubmitActionLabel('Import & Download Credentials')
                ->extraModalFooterActions([
                    Action::make('downloadTemplate')
                        ->label('Upload Template')
                        ->color('gray')
                        ->action(fn () => response()->download(
                            public_path('templates/users-import-template.csv'),
                            'users-import-template.csv',
                            ['Content-Type' => 'text/csv']
                        )),
                ])
                ->schema([
                    FileUpload::make('csv_file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                        ->disk('local')
                        ->directory('csv-imports')
                        ->required(),
                ])
                ->action(function (array $data): mixed {
                    $path = storage_path('app/private/'.$data['csv_file']);

                    if (! file_exists($path)) {
                        Notification::make()->title('File not found.')->danger()->send();

                        return null;
                    }

                    $handle = fopen($path, 'r');
                    $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

                    if (! in_array('name', $headers) || ! in_array('email', $headers)) {
                        fclose($handle);
                        Notification::make()
                            ->title('Invalid CSV format. Required columns: name, email.')
                            ->danger()
                            ->send();

                        return null;
                    }

                    $nameIdx = array_search('name', $headers);
                    $emailIdx = array_search('email', $headers);
                    $phoneIdx = array_search('phone', $headers);
                    $departmentIdx = array_search('department_id', $headers);
                    $currencyIdx = array_search('currency_id', $headers);
                    $countryIdx = array_search('country_id', $headers);

                    /** @var Collection<int, array{name: string, email: string, password: string}> $credentials */
                    $credentials = collect();
                    $errors = [];
                    $rowNumber = 1;

                    $rows = [];
                    while (($row = fgetcsv($handle)) !== false) {
                        $rowNumber++;
                        $name = trim($row[$nameIdx] ?? '');
                        $email = strtolower(trim($row[$emailIdx] ?? ''));
                        $phone = $phoneIdx !== false ? trim($row[$phoneIdx] ?? '') : null;
                        $departmentId = $departmentIdx !== false ? (trim($row[$departmentIdx] ?? '') ?: null) : null;
                        $currencyId = $currencyIdx !== false ? (trim($row[$currencyIdx] ?? '') ?: null) : null;
                        $countryId = $countryIdx !== false ? (trim($row[$countryIdx] ?? '') ?: null) : null;

                        $validation = Validator::make(
                            ['name' => $name, 'email' => $email, 'department_id' => $departmentId, 'currency_id' => $currencyId, 'country_id' => $countryId],
                            [
                                'name' => 'required|string',
                                'email' => 'required|email',
                                'department_id' => 'nullable|integer|exists:departments,id',
                                'currency_id' => 'nullable|integer|exists:currencies,id',
                                'country_id' => 'nullable|integer|exists:countries,id',
                            ]
                        );

                        if ($validation->fails()) {
                            $errors[] = "Row {$rowNumber}: ".implode(', ', $validation->errors()->all());

                            continue;
                        }

                        $rows[] = compact('name', 'email', 'phone', 'departmentId', 'currencyId', 'countryId');
                    }

                    fclose($handle);
                    @unlink($path);

                    if (! empty($errors) && empty($rows)) {
                        Notification::make()
                            ->title('No valid rows found. Errors: '.implode(' | ', array_slice($errors, 0, 5)))
                            ->danger()
                            ->send();

                        return null;
                    }

                    $duplicateEmails = User::query()
                        ->whereIn('email', array_column($rows, 'email'))
                        ->pluck('email')
                        ->all();

                    DB::transaction(function () use ($rows, $duplicateEmails, &$credentials, &$errors, &$rowNumber) {
                        foreach ($rows as $row) {
                            if (in_array($row['email'], $duplicateEmails)) {
                                $errors[] = "Skipped {$row['email']}: email already exists.";

                                continue;
                            }

                            $plainPassword = Str::password(10, letters: true, numbers: true, symbols: false);

                            $user = User::create([
                                'name' => $row['name'],
                                'email' => $row['email'],
                                'phone' => $row['phone'] ?: null,
                                'department_id' => $row['departmentId'],
                                'currency_id' => $row['currencyId'],
                                'country_id' => $row['countryId'],
                                'password' => bcrypt($plainPassword),
                            ]);

                            $user->assignRole('staff');

                            $credentials->push([
                                'name' => $user->name,
                                'email' => $user->email,
                                'password' => $plainPassword,
                            ]);
                        }
                    });

                    if ($credentials->isEmpty()) {
                        Notification::make()
                            ->title('No users were imported. '.implode(' | ', array_slice($errors, 0, 5)))
                            ->danger()
                            ->send();

                        return null;
                    }

                    $skipped = count($errors);
                    $imported = $credentials->count();

                    Notification::make()
                        ->title("{$imported} user(s) imported.".($skipped > 0 ? " {$skipped} row(s) skipped." : ''))
                        ->success()
                        ->send();

                    return Excel::download(
                        new BulkImportResultExport($credentials),
                        'imported-users-'.now()->format('Y-m-d-His').'.xlsx'
                    );
                }),
            CreateAction::make(),
        ];
    }
}
