<x-filament-panels::page>
    <form wire:submit.prevent="export">
        {{ $this->form }}
    </form>

    @if ($showPreview)
        @if (!empty($previewExpenses))
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-white/10">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Expenses
                        <span class="ml-2 text-sm font-normal text-gray-500">
                            ({{ count(array_filter($previewExpenses, fn($r) => ($r[6] ?? '') !== 'SUBTOTAL')) }} line items)
                        </span>
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="fi-ta-table">
                        <thead>
                            <tr class="bg-gray-900 text-white dark:bg-gray-950">
                                @foreach(['No.','Tx Date', 'Expense Ref', 'Project', 'Staff', 'Email', 'Title', 'Description', 'Qty', 'Rate', 'Amount (Local)', 'Total (USD)', 'Payment Method'] as $heading)
                                    <th class="" style="text-align: left;">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewExpenses as $row)
                                @php $isSubtotal = ($row[6] ?? '') === 'SUBTOTAL'; @endphp
                                <tr @class([
                                    'border-t border-gray-100 dark:border-white/5',
                                    'bg-gray-100 font-semibold dark:bg-gray-800' => $isSubtotal,
                                    'hover:bg-gray-50 dark:hover:bg-white/5' => !$isSubtotal,
                                ])>
                                    @foreach($row as $cell)
                                        <td class="whitespace-nowrap px-4 py-2 text-gray-700 dark:text-gray-300">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (!empty($previewDisbursements))
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-white/10">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Disbursements
                        <span class="ml-2 text-sm font-normal text-gray-500">
                            ({{ count($previewDisbursements) }} records)
                        </span>
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="fi-ta-table">
                        <thead>
                            <tr class="bg-gray-900 text-white dark:bg-gray-950">
                                @foreach(['No.','Tx Date','Disbursement Ref', 'Project', 'Staff', 'Email', 'Disbursement Type', 'Amount (Local)', 'Total (USD)', 'Payment Method', 'Status'] as $heading)
                                    <th class="" style="text-align: left;">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewDisbursements as $row)
                                <tr class="border-t border-gray-100 hover:bg-gray-50 dark:border-white/5 dark:hover:bg-white/5">
                                    @foreach($row as $cell)
                                        <td class="whitespace-nowrap px-4 py-2 text-gray-700 dark:text-gray-300">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (empty($previewExpenses) && empty($previewDisbursements))
            <div class="rounded-xl bg-white px-6 py-10 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm text-gray-500">No records found for the selected filters.</p>
            </div>
        @endif
    @endif
</x-filament-panels::page>
