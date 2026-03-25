<?php

namespace App\Exports\Sheets;

use App\Models\Expense;
use App\Models\PendingPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesSheet implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /** @var list<int> */
    private array $subtotalRows = [];

    public function __construct(private readonly array $filters) {}

    public function title(): string
    {
        return 'Expenses';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'No.', 'Expense Ref', 'Project', 'Staff', 'Email',
            'Title', 'Description', 'Qty', 'Rate',
            'Amount (Local)', 'Total (USD)', 'Payment Method',
        ];
    }

    public function collection(): Collection
    {
        $query = PendingPayment::query()
            ->where('payable_type', Expense::class)
            ->with([
                'payable.lineItems',
                'payable.expenseType',
                'payable.project',
                'payable.user.currency',
                'payable.currency',
                'paymentMethod',
            ]);

        $this->applyFilters($query);

        $rows = collect();
        $counter = 1;
        $rowIdx = 2; // 1-indexed, row 1 is header

        foreach ($query->get() as $payment) {
            $expense = $payment->payable;
            $currency = $expense->currency;
            $symbol = $currency?->symbol ?? '';
            $rate = (float) ($currency?->conversion_rate ?? 1);
            $pmName = $payment->paymentMethod?->name ?? '—';
            $expenseRef = $expense->ref();
            $project = $expense->project?->name ?? '—';
            $staff = $expense->user?->name ?? '—';
            $email = $expense->user?->email ?? '—';
            $typeLabel = $expense->expenseType?->name ?? '—';

            $expenseTotal = 0.0;

            foreach ($expense->lineItems as $item) {
                $lineTotal = (float) $item->total;
                $expenseTotal += $lineTotal;

                $rows->push([
                    $counter++,
                    $expenseRef,
                    $project,
                    $staff,
                    $email,
                    $typeLabel,
                    $item->description,
                    (float) $item->quantity,
                    number_format((float) $item->unit_price, 2),
                    $symbol.number_format($lineTotal, 2),
                    number_format($rate > 0 ? $lineTotal / $rate : 0, 2),
                    $pmName,
                ]);
                $rowIdx++;
            }

            // Subtotal row
            $this->subtotalRows[] = $rowIdx;
            $rows->push([
                '', '', '', '', '', '', 'SUBTOTAL', '', '',
                $symbol.number_format($expenseTotal, 2),
                number_format($rate > 0 ? $expenseTotal / $rate : 0, 2),
                '',
            ]);
            $rowIdx++;
        }

        return $rows;
    }

    public function map($row): array
    {
        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1e293b'],
                ],
            ],
        ];

        foreach ($this->subtotalRows as $rowNum) {
            $styles[$rowNum] = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'f1f5f9'],
                ],
            ];
        }

        return $styles;
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 5,  'B' => 12, 'C' => 18, 'D' => 18,
            'E' => 28, 'F' => 20, 'G' => 30, 'H' => 8,
            'I' => 12, 'J' => 16, 'K' => 14, 'L' => 20,
        ];
    }

    /**
     * @return array<string, \Closure>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$highestCol}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    private function applyFilters(Builder $query): void
    {
        if (! empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (! empty($this->filters['currency_id'])) {
            $query->where('currency_id', $this->filters['currency_id']);
        }
        if (! empty($this->filters['status'])) {
            $query->whereHas('payable', fn ($q) => $q->where('status', $this->filters['status']));
        }
        if (! empty($this->filters['project_id'])) {
            $query->whereHas('payable', fn ($q) => $q->where('project_id', $this->filters['project_id']));
        }
    }
}
