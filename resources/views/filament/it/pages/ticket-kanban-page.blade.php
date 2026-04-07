<x-filament-panels::page>
    <div
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4"
        x-data="{
            dragging: null,
            dragOver: null,
            onDragStart(ticketId, column) {
                this.dragging = { ticketId, column };
            },
            onDragOver(column) {
                this.dragOver = column;
            },
            onDrop(newStatus) {
                if (this.dragging && this.dragging.column !== newStatus) {
                    $wire.updateTicketStatus(this.dragging.ticketId, newStatus);
                }
                this.dragging = null;
                this.dragOver = null;
            }
        }"
    >
        @foreach($columns as $status => $tickets)
            <div
                class="bg-gray-100 dark:bg-gray-800 rounded-xl p-3 min-h-64"
                x-on:dragover.prevent="onDragOver('{{ $status }}')"
                x-on:drop.prevent="onDrop('{{ $status }}')"
                :class="{ 'ring-2 ring-primary-500': dragOver === '{{ $status }}' }"
            >
                <h3 class="font-semibold text-sm uppercase tracking-wide text-gray-600 dark:text-gray-300 mb-3 px-1">
                    {{ $columnLabels[$status] ?? $status }}
                    <span class="ml-1 text-xs bg-gray-200 dark:bg-gray-700 rounded-full px-2 py-0.5">{{ count($tickets) }}</span>
                </h3>

                <div class="space-y-2">
                    @foreach($tickets as $ticket)
                        <div
                            draggable="true"
                            x-on:dragstart="onDragStart({{ $ticket['id'] }}, '{{ $status }}')"
                            class="bg-white dark:bg-gray-900 rounded-lg p-3 shadow-sm cursor-grab border border-gray-200 dark:border-gray-700 hover:border-primary-400 transition"
                        >
                            <a href="{{ $ticket['edit_url'] }}" class="block">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $ticket['ticket_number'] }}</span>
                                    <span class="text-xs px-1.5 py-0.5 rounded font-medium"
                                        style="background-color: {{ $ticket['priority_color_css'] }}20; color: {{ $ticket['priority_color_css'] }}">
                                        {{ $ticket['priority_label'] }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 leading-tight line-clamp-2">
                                    {{ $ticket['title'] }}
                                </p>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="w-6 h-6 rounded-full bg-primary-500 text-white text-xs flex items-center justify-center font-bold">
                                        {{ $ticket['requester_initials'] }}
                                    </div>
                                    @if($ticket['sla_countdown_hours'] !== null)
                                        <span class="text-xs px-1.5 py-0.5 rounded {{ $ticket['sla_color'] }}">
                                            {{ $ticket['sla_countdown_hours'] }}h
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
