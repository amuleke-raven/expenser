<?php

namespace App\Observers;

use App\Models\ExpenseLineItem;

class ExpenseLineItemObserver
{
    public function saved(ExpenseLineItem $lineItem): void
    {
        $lineItem->expense->recalculateTotal();
    }

    public function deleted(ExpenseLineItem $lineItem): void
    {
        $lineItem->expense->recalculateTotal();
    }
}
