<?php

namespace App\Exports\Sheets;

use App\Models\PendingPayment;
use App\Models\RewardRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RewardsSheet implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly array $filters) {}

    public function title(): string
    {
        return 'Rewards';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'No.', 'Reward Ref', 'Project', 'Staff', 'Email',
            'Reward Type', 'Amount (Local)', 'Total (USD)',
            'Payment Method', 'Status',
        ];
    }

    public function collection(): Collection
    {
        $query = PendingPayment::query()
            ->where('payable_type', RewardRecipient::class)
            ->with([
                'payable.reward.rewardType',
                'payable.reward.project',
                'payable.reward.currency',
                'payable.user',
                'paymentMethod',
            ]);

        $this->applyFilters($query);

        $counter = 1;

        return $query->get()->map(function (PendingPayment $payment) use (&$counter) {
            $recipient = $payment->payable;
            $reward = $recipient?->reward;
            $currency = $reward?->currency;
            $symbol = $currency?->symbol ?? '';
            $rate = (float) ($currency?->conversion_rate ?? 1);
            $amount = (float) ($reward?->amount ?? 0);

            return [
                $counter++,
                $reward?->ref() ?? '—',
                $reward?->project?->name ?? '—',
                $recipient?->user?->name ?? '—',
                $recipient?->user?->email ?? '—',
                $reward?->rewardType?->name ?? '—',
                $symbol.number_format($amount, 2),
                number_format($rate > 0 ? $amount / $rate : 0, 2),
                $payment->paymentMethod?->name ?? '—',
                $payment->status->label(),
            ];
        });
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

        $highestRow = $sheet->getHighestRow();
        for ($i = 3; $i <= $highestRow; $i += 2) {
            $styles[$i] = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'f8fafc'],
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
            'E' => 28, 'F' => 22, 'G' => 16, 'H' => 14,
            'I' => 20, 'J' => 12,
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
        if (! empty($this->filters['project_id'])) {
            $query->whereHas('payable.reward', fn ($q) => $q->where('project_id', $this->filters['project_id']));
        }
    }
}
