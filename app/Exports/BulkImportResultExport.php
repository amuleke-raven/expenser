<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BulkImportResultExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /** @param Collection<int, array{name: string, email: string, password: string}> $rows */
    public function __construct(private readonly Collection $rows) {}

    public function title(): string
    {
        return 'Imported Users';
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['Name', 'Email', 'Generated Password'];
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    /** @param array{name: string, email: string, password: string} $row */
    public function map($row): array
    {
        return [$row['name'], $row['email'], $row['password']];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1e293b'],
                ],
            ],
        ];
    }

    /** @return array<string, int> */
    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 36, 'C' => 24];
    }
}
