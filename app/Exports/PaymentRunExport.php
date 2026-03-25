<?php

namespace App\Exports;

use App\Exports\Sheets\ExpensesSheet;
use App\Exports\Sheets\RewardsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PaymentRunExport implements WithMultipleSheets
{
    public function __construct(public readonly array $filters) {}

    public function sheets(): array
    {
        return [
            new ExpensesSheet($this->filters),
            new RewardsSheet($this->filters),
        ];
    }
}
