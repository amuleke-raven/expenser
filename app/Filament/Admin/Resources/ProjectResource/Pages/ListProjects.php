<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importProjects')
                ->label('Import via CSV')
                ->icon(Heroicon::ArrowUpTray)
                ->visible(fn (): bool => auth()->user()->hasRole('super_admin'))
                ->modalHeading('Bulk Import Projects')
                ->modalDescription('Upload a CSV file with columns: name. Optional columns: client_name, is_active (1/0), is_default (1/0).')
                ->modalSubmitActionLabel('Import')
                ->extraModalFooterActions([
                    Action::make('downloadProjectTemplate')
                        ->label('Upload Template')
                        ->color('gray')
                        ->action(fn () => response()->download(
                            public_path('templates/projects-import-template.csv'),
                            'projects-import-template.csv',
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
                ->action(function (array $data): void {
                    $path = storage_path('app/private/'.$data['csv_file']);

                    if (! file_exists($path)) {
                        Notification::make()->title('File not found.')->danger()->send();

                        return;
                    }

                    $handle = fopen($path, 'r');
                    $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

                    if (! in_array('name', $headers)) {
                        fclose($handle);
                        Notification::make()
                            ->title('Invalid CSV format. Required column: name.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $nameIdx = array_search('name', $headers);
                    $clientIdx = array_search('client_name', $headers);
                    $activeIdx = array_search('is_active', $headers);
                    $defaultIdx = array_search('is_default', $headers);

                    $errors = [];
                    $rows = [];
                    $rowNumber = 1;

                    while (($row = fgetcsv($handle)) !== false) {
                        $rowNumber++;
                        $name = trim($row[$nameIdx] ?? '');

                        $validation = Validator::make(
                            ['name' => $name],
                            ['name' => 'required|string']
                        );

                        if ($validation->fails()) {
                            $errors[] = "Row {$rowNumber}: ".implode(', ', $validation->errors()->all());

                            continue;
                        }

                        $rows[] = [
                            'name' => $name,
                            'client_name' => $clientIdx !== false ? (trim($row[$clientIdx] ?? '') ?: null) : null,
                            'is_active' => $activeIdx !== false ? (bool) ($row[$activeIdx] ?? 1) : true,
                            'is_default' => $defaultIdx !== false ? (bool) ($row[$defaultIdx] ?? 0) : false,
                        ];
                    }

                    fclose($handle);
                    @unlink($path);

                    if (! empty($errors) && empty($rows)) {
                        Notification::make()
                            ->title('No valid rows found. Errors: '.implode(' | ', array_slice($errors, 0, 5)))
                            ->danger()
                            ->send();

                        return;
                    }

                    $existingNames = Project::query()
                        ->whereIn('name', array_column($rows, 'name'))
                        ->pluck('name')
                        ->map(fn (string $n) => strtolower($n))
                        ->all();

                    $imported = 0;

                    DB::transaction(function () use ($rows, $existingNames, &$imported, &$errors) {
                        foreach ($rows as $row) {
                            if (in_array(strtolower($row['name']), $existingNames)) {
                                $errors[] = "Skipped \"{$row['name']}\": project already exists.";

                                continue;
                            }

                            Project::create($row);
                            $imported++;
                        }
                    });

                    if ($imported === 0) {
                        Notification::make()
                            ->title('No projects were imported. '.implode(' | ', array_slice($errors, 0, 5)))
                            ->danger()
                            ->send();

                        return;
                    }

                    $skipped = count($errors);

                    Notification::make()
                        ->title("{$imported} project(s) imported.".($skipped > 0 ? " {$skipped} row(s) skipped." : ''))
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
