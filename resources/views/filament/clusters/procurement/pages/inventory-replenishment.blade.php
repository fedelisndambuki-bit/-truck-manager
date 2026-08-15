@php $rows = $this->getRecommendations(); @endphp
<x-filament-panels::page>
    @if ($rows->isEmpty())
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">No materials are currently below their reorder level.</p>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Material</th>
                            <th class="px-3 py-2">On Hand</th>
                            <th class="px-3 py-2">Reorder Level</th>
                            <th class="px-3 py-2">Max Stock</th>
                            <th class="px-3 py-2">Recommended Qty</th>
                            <th class="px-3 py-2">Est. Cost</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-t border-gray-100 dark:border-white/5">
                                <td class="px-3 py-2 font-medium">{{ $row['material']->name }}</td>
                                <td class="px-3 py-2">{{ number_format($row['quantity_on_hand'], 2) }}</td>
                                <td class="px-3 py-2">{{ number_format($row['reorder_level'], 2) }}</td>
                                <td class="px-3 py-2">{{ $row['max_stock'] !== null ? number_format($row['max_stock'], 2) : '—' }}</td>
                                <td class="px-3 py-2 font-semibold">{{ number_format($row['recommended_quantity'], 2) }}</td>
                                <td class="px-3 py-2">{{ number_format($row['estimated_cost'], 2) }}</td>
                                <td class="px-3 py-2">
                                    @if ($row['has_open_requisition'])
                                        <span class="fi-badge inline-flex items-center rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-500/10 dark:text-gray-400">Requisition Open</span>
                                    @else
                                        <button
                                            wire:click="createRequisition({{ $row['material']->id }})"
                                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-8 px-3 text-xs inline-grid gap-1 shadow-sm bg-primary-600 text-white hover:bg-primary-500"
                                        >
                                            Create Requisition
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
