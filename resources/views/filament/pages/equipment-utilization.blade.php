<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @php
        $stats = $this->getStats();
        $report = $this->getEquipmentReport();
    @endphp

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
        'title' => 'Equipment Utilization',
        'subtitle' => 'Operating hours, fuel consumption and machine status',
    ])

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <x-filament::section>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Equipment</div>
            <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $stats['total'] }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Utilization</div>
            <div class="mt-1 text-2xl font-bold text-success-600 dark:text-success-400">{{ $stats['utilization_percent'] }}%</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Idle</div>
            <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $stats['idle'] }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Under Maintenance</div>
            <div class="mt-1 text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $stats['maintenance'] }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Fuel Consumed Today</div>
            <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($stats['fuel_today'], 1) }} L</div>
        </x-filament::section>
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Machine Utilization</div>
            <div class="table-results">{{ $report->count() }} machines</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Hours Today</th>
                    <th>Fuel Today (L)</th>
                    <th>Allocations</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report as $equipment)
                    @php
                        $color = match ($equipment->status) {
                            'Available' => 'success',
                            'In Use' => 'warning',
                            'Under Maintenance' => 'danger',
                            default => 'gray',
                        };
                    @endphp
                    <tr>
                        <td style="font-weight:600;color:#111827;">{{ $equipment->name }}</td>
                        <td>{{ $equipment->equipment_type }}</td>
                        <td><span class="badge {{ $color }}">{{ $equipment->status }}</span></td>
                        <td>{{ number_format($equipment->hours_today ?? 0, 1) }}</td>
                        <td>{{ number_format($equipment->fuel_today ?? 0, 1) }}</td>
                        <td>{{ $equipment->allocations_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-row">No equipment records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
