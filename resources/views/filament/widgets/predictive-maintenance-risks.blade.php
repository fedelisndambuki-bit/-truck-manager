<x-filament::section>
    <x-slot name="heading">Top Predictive Maintenance Risks</x-slot>
    <div class="space-y-2">
        @if(empty($risks))
            <div class="text-sm text-gray-500">No notable risks detected.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr>
                            <th class="text-left py-2 pr-4">Truck</th>
                            <th class="text-left py-2 pr-4">Prob%</th>
                            <th class="text-left py-2 pr-4">RUL (days)</th>
                            <th class="text-left py-2 pr-4">Window</th>
                            <th class="text-left py-2 pr-4">Avg km/day</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($risks as $r)
                            <tr>
                                <td class="py-2 pr-4">{{ $r['plate_number'] }}</td>
                                <td class="py-2 pr-4">{{ $r['probability'] }}%</td>
                                <td class="py-2 pr-4">{{ $r['rul_days'] ?? 'N/A' }}</td>
                                <td class="py-2 pr-4">{{ $r['service_window'][0] }} → {{ $r['service_window'][1] }}</td>
                                <td class="py-2 pr-4">{{ $r['avg_daily_km'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="flex justify-end">
            <x-filament::button size="sm" color="gray" outlined wire:click="refreshRisks">Refresh</x-filament::button>
        </div>
    </div>
</x-filament::section>
