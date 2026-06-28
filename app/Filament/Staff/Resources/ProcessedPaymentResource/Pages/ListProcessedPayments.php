<?php

namespace App\Filament\Staff\Resources\ProcessedPaymentResource\Pages;

use App\Exports\ProcessedPaymentsExport;
use App\Filament\Staff\Resources\ProcessedPaymentResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class ListProcessedPayments extends ListRecords
{
    protected static string $resource = ProcessedPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->schema([
                    Select::make('format')
                        ->label('Format')
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                        ])
                        ->default('xlsx')
                        ->required(),
                ])
                ->action(function (array $data): mixed {
                    $extension = $data['format'] === 'csv' ? 'csv' : 'xlsx';
                    $writerType = $data['format'] === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

                    return Excel::download(
                        new ProcessedPaymentsExport($this->getFilteredTableQuery()),
                        'processed-payments-'.now()->format('Y-m-d-His').'.'.$extension,
                        $writerType,
                    );
                }),
        ];
    }
}
