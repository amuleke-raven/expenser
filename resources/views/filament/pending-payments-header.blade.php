<div class="fi-ta-header flex w-full flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:px-6">
    <div class="flex-1">
        <h3 class="fi-ta-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
            Pending Payments
        </h3>
    </div>

    <div
        x-data="{
            amounts: @js($this->pendingPaymentAmountsUsd),
            selected: $wire.$entangle('selectedTableRecordIds'),
            get total() {
                return (this.selected || []).reduce((sum, id) => sum + (this.amounts[String(id)] || 0), 0);
            }
        }"
        x-show="total > 0"
        class="inline-flex items-center gap-x-1.5 rounded-md bg-success-50 px-3 py-1.5 text-sm font-semibold text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30"
    >
        <span>Selected total:</span>
        <span>$<span x-text="total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span> USD</span>
    </div>
</div>
