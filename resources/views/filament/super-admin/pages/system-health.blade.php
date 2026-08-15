<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">CPU Usage</p>
            <p class="text-xl font-semibold text-gray-900">{{ $cpu !== null ? number_format((float) $cpu, 2) . '%' : 'N/A' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">RAM Usage</p>
            <p class="text-xl font-semibold text-gray-900">{{ $ram !== null ? number_format((float) $ram, 2) . '%' : 'N/A' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">Disk Usage</p>
            <p class="text-xl font-semibold text-gray-900">{{ $diskUsedPercent !== null ? number_format((float) $diskUsedPercent, 2) . '%' : 'N/A' }}</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">Services</h3>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><span>Database</span><span class="{{ $dbStatus === 'up' ? 'text-green-600' : 'text-red-600' }}">{{ strtoupper($dbStatus) }}</span></div>
                <div class="flex justify-between"><span>Queue Pending</span><span>{{ $queuePending ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span>Queue Failed</span><span>{{ $queueFailed ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span>Ollama</span><span class="{{ $ollamaStatus === 'up' ? 'text-green-600' : 'text-red-600' }}">{{ strtoupper($ollamaStatus) }}</span></div>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">Environment</h3>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><span>PHP Version</span><span>{{ $phpVersion }}</span></div>
                <div class="flex justify-between"><span>App Environment</span><span>{{ strtoupper((string) $appEnv) }}</span></div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
