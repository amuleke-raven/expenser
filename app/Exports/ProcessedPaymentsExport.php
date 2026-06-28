<?php

namespace App\Exports;

use App\Enums\PaymentSource;
use App\Models\Expense;
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

class ProcessedPaymentsExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly Builder $query) {}

    public function title(): string
    {
        return 'Processed Payments';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Type', 'Ref', 'Recipient', 'Email', 'Requester', 'Disbursement Type',
            'Billable', 'Date Requested', 'Amount', 'Currency', 'Amount (USD)',
            'Payment Method', 'Processed By', 'Processed At',
        ];
    }

    public function collection(): Collection
    {
        return $this->query
            ->with([
                'currency',
                'paymentMethod',
                'processedBy',
                'recipientUser',
                'rewardRecipient.user',
                'payable' => fn ($morphTo) => $morphTo->morphWith([
                    Expense::class => ['user', 'expenseType'],
                    RewardRecipient::class => ['reward.initiatedBy', 'reward.rewardType'],
                ]),
            ])
            ->get();
    }

    /**
     * @return list<string>
     */
    public function map($payment): array
    {
        /** @var PendingPayment $payment */
        $rate = max((float) ($payment->currency?->conversion_rate ?? 1), 0.000001);

        return [
            $this->typeLabel($payment),
            $this->ref($payment),
            $this->recipientName($payment),
            $this->recipientEmail($payment),
            $this->requesterName($payment),
            $this->disbursementType($payment),
            $this->isBillable($payment) === null ? '—' : ($this->isBillable($payment) ? 'Yes' : 'No'),
            $this->dateRequested($payment),
            number_format((float) $payment->amount, 2),
            $payment->currency?->code ?? '—',
            '$'.number_format((float) $payment->amount / $rate, 2),
            $payment->paymentMethod?->name ?? $payment->manual_payment_details ?? '—',
            $payment->processedBy?->name ?? '—',
            $payment->processed_at?->format('d M Y, H:i') ?? '—',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 14, 'C' => 22, 'D' => 28, 'E' => 22, 'F' => 20,
            'G' => 10, 'H' => 16, 'I' => 14, 'J' => 10, 'K' => 16,
            'L' => 22, 'M' => 20, 'N' => 20,
        ];
    }

    private function typeLabel(PendingPayment $payment): string
    {
        return match ($payment->payable_type) {
            Expense::class => 'Expense',
            RewardRecipient::class => 'Disbursement',
            default => (string) $payment->payable_type,
        };
    }

    private function ref(PendingPayment $payment): string
    {
        return match (true) {
            $payment->payable instanceof Expense => $payment->payable->ref(),
            $payment->payable instanceof RewardRecipient => $payment->payable->reward?->ref() ?? '—',
            default => '—',
        };
    }

    private function recipientName(PendingPayment $payment): string
    {
        if ($payment->payment_source === PaymentSource::Expense) {
            return $payment->recipientUser?->name ?? '—';
        }

        return $payment->rewardRecipient?->user?->name
            ?? $payment->rewardRecipient?->name
            ?? '—';
    }

    private function recipientEmail(PendingPayment $payment): string
    {
        if ($payment->payment_source === PaymentSource::Expense) {
            return $payment->recipientUser?->email ?? '—';
        }

        return $payment->rewardRecipient?->user?->email
            ?? $payment->rewardRecipient?->email
            ?? '—';
    }

    private function requesterName(PendingPayment $payment): string
    {
        if ($payment->payable instanceof Expense) {
            return $payment->payable->user?->name ?? '—';
        }

        if ($payment->payable instanceof RewardRecipient) {
            return $payment->payable->reward?->initiatedBy?->name ?? '—';
        }

        return '—';
    }

    private function disbursementType(PendingPayment $payment): string
    {
        if ($payment->payable instanceof Expense) {
            return $payment->payable->expenseType?->name ?? '—';
        }

        if ($payment->payable instanceof RewardRecipient) {
            return $payment->payable->reward?->rewardType?->name ?? '—';
        }

        return '—';
    }

    private function isBillable(PendingPayment $payment): ?bool
    {
        if ($payment->payable instanceof Expense) {
            return $payment->payable->is_billable;
        }

        if ($payment->payable instanceof RewardRecipient) {
            return $payment->payable->reward?->is_billable;
        }

        return null;
    }

    private function dateRequested(PendingPayment $payment): string
    {
        if ($payment->payable instanceof Expense) {
            $date = $payment->payable->submitted_at ?? $payment->payable->created_at;

            return $date?->format('M j, Y') ?? '—';
        }

        if ($payment->payable instanceof RewardRecipient) {
            return $payment->payable->reward?->created_at?->format('M j, Y') ?? '—';
        }

        return '—';
    }
}
