<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    @php $rows = $this->getComparisonRows(); @endphp

    @if ($rows->isNotEmpty())
        <x-filament::section class="mt-4">
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2">Supplier</th>
                            <th class="px-3 py-2">Price</th>
                            <th class="px-3 py-2">Lead Time</th>
                            <th class="px-3 py-2">Warranty</th>
                            <th class="px-3 py-2">Delivery Terms</th>
                            <th class="px-3 py-2">Rating</th>
                            <th class="px-3 py-2">Quality</th>
                            <th class="px-3 py-2">Risk</th>
                            <th class="px-3 py-2">Score</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-t border-gray-100 dark:border-white/5 {{ $row['recommended'] ? 'bg-success-50 dark:bg-success-500/10' : '' }}">
                                <td class="px-3 py-2 font-medium">
                                    {{ $row['supplier_name'] }}
                                    @if ($row['recommended'])
                                        <span class="fi-badge inline-flex items-center rounded-md bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-500/10 dark:text-success-400 ml-1">Recommended</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ number_format($row['price'], 2) }}</td>
                                <td class="px-3 py-2">{{ $row['lead_time_days'] ?? '—' }} days</td>
                                <td class="px-3 py-2">{{ $row['warranty'] ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $row['delivery_terms'] ?? '—' }}</td>
                                <td class="px-3 py-2">{{ number_format($row['supplier_rating'], 1) }} / 5</td>
                                <td class="px-3 py-2">{{ number_format($row['quality_score'], 1) }} / 5</td>
                                <td class="px-3 py-2">{{ $row['risk_rating'] ?? '—' }}</td>
                                <td class="px-3 py-2 font-semibold">{{ $row['overall_score'] }}</td>
                                <td class="px-3 py-2">
                                    @if ($row['quotation']->status === 'Submitted' || $row['quotation']->status === 'Under Review')
                                        <button
                                            wire:click="awardQuotation({{ $row['quotation']->id }})"
                                            wire:confirm="Award this RFQ to {{ $row['supplier_name'] }}? This creates a Purchase Order and rejects the other quotations."
                                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-8 px-3 text-xs inline-grid gap-1 shadow-sm bg-primary-600 text-white hover:bg-primary-500"
                                        >
                                            Award
                                        </button>
                                    @else
                                        <span class="fi-badge inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium
                                            {{ $row['quotation']->status === 'Accepted' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-50 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400' }}">
                                            {{ $row['quotation']->status }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @elseif ($this->rfq_id)
        <x-filament::section class="mt-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">No quotations have been submitted for this RFQ yet.</p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
