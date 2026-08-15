<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">MRR</p>
            <p class="text-xl font-semibold text-gray-900">KES {{ number_format((float) $mrr, 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">ARR</p>
            <p class="text-xl font-semibold text-gray-900">KES {{ number_format((float) $arr, 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">Churn Rate</p>
            <p class="text-xl font-semibold text-gray-900">{{ number_format((float) $churnRate, 2) }}%</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">New Signups (Month)</p>
            <p class="text-xl font-semibold text-gray-900">{{ $newSignups }}</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">Plan Distribution</h3>
            <div class="mt-3 space-y-2">
                @forelse($planDistribution as $row)
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2">
                        <span class="text-sm text-gray-700">{{ $row->plan_name ?: 'Unknown Plan' }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $row->total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No plan distribution data.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">Growth Trend (Last 6 Months)</h3>
            <div class="mt-3 space-y-2">
                @foreach($monthlySeries as $point)
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2">
                        <span class="text-sm text-gray-700">{{ $point['label'] }}</span>
                        <span class="text-xs text-gray-600">Revenue: KES {{ number_format((float) $point['revenue'], 2) }} | Signups: {{ $point['signups'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
