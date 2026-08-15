@php
    $summary = $this->getSummary();
@endphp
<x-filament-panels::page>
    <x-filament-widgets::widgets :widgets="$this->getHeaderWidgets()" :columns="2" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-filament::section>
            <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Average Yield % (30 Days)</div>
            <div class="mt-1 text-2xl font-semibold text-success-600 dark:text-success-400">{{ $summary['yield_percent'] }}%</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Average Waste % (30 Days)</div>
            <div class="mt-1 text-2xl font-semibold text-danger-600 dark:text-danger-400">{{ $summary['waste_percent'] }}%</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Recent Production — Yield Breakdown</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pr-4">Production #</th>
                        <th class="py-2 pr-4">Quarry</th>
                        <th class="py-2 pr-4">Date</th>
                        <th class="py-2 pr-4">Feed</th>
                        <th class="py-2 pr-4">Finished</th>
                        <th class="py-2 pr-4">Waste</th>
                        <th class="py-2 pr-4">Recovery %</th>
                        <th class="py-2 pr-4">Yield %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getBreakdown() as $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="py-2 pr-4 font-medium">{{ $row['production_number'] }}</td>
                            <td class="py-2 pr-4">{{ $row['site'] }}</td>
                            <td class="py-2 pr-4">{{ $row['date'] }}</td>
                            <td class="py-2 pr-4">{{ number_format($row['feed'], 1) }} t</td>
                            <td class="py-2 pr-4">{{ number_format($row['finished'], 1) }} t</td>
                            <td class="py-2 pr-4">{{ number_format($row['waste'], 1) }} t</td>
                            <td class="py-2 pr-4">{{ number_format($row['recovery'], 1) }}%</td>
                            <td class="py-2 pr-4 font-semibold">{{ number_format($row['yield'], 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-gray-500 dark:text-gray-400">No approved production records with yield data yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
