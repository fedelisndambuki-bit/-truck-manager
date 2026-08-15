@php
    $currency = session('currency', 'KES');
    $monthly = $this->getMonthlyRows();
    $projects = $this->getProjectRows();
    $totalIn = array_sum(array_column($monthly, 'in'));
    $totalOut = array_sum(array_column($monthly, 'out'));
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75m-18-15v6.75c0 .414.336.75.75.75h16.5a.75.75 0 00.75-.75V6.75m-18 0h18M2.25 6.75L4.5 4.5m0 0L6.75 6.75M4.5 4.5v15" />',
        'title' => 'Cash Flow',
        'subtitle' => 'Cash in, cash out and projected position across projects',
    ])

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-filament::section>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cash In (12mo)</div>
            <div class="text-xl font-bold text-success-600 dark:text-success-400 mt-1">{{ $currency }} {{ number_format($totalIn, 0) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cash Out (12mo)</div>
            <div class="text-xl font-bold text-danger-600 dark:text-danger-400 mt-1">{{ $currency }} {{ number_format($totalOut, 0) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Net Cash Flow</div>
            <div class="text-xl font-bold {{ ($totalIn - $totalOut) >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }} mt-1">
                {{ $currency }} {{ number_format($totalIn - $totalOut, 0) }}
            </div>
        </x-filament::section>
    </div>

    <div class="table-section">
        <div class="table-header-custom"><div class="table-title">Monthly Cash Flow</div></div>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Month</th><th>Cash In</th><th>Cash Out</th><th>Net</th></tr></thead>
                <tbody>
                    @forelse ($monthly as $row)
                        <tr>
                            <td class="row-title">{{ $row['month'] }}</td>
                            <td>{{ $currency }} {{ number_format($row['in'], 0) }}</td>
                            <td>{{ $currency }} {{ number_format($row['out'], 0) }}</td>
                            <td><span class="badge {{ $row['net'] >= 0 ? 'success' : 'danger' }}">{{ $currency }} {{ number_format($row['net'], 0) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No cash flow data yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header-custom"><div class="table-title">Project Cash Flow</div></div>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Project</th><th>Cash In</th><th>Cash Out</th><th>Outstanding</th><th>Projected</th></tr></thead>
                <tbody>
                    @forelse ($projects as $row)
                        <tr>
                            <td class="row-title">{{ $row['project']->name }}</td>
                            <td>{{ $currency }} {{ number_format($row['cash_in'], 0) }}</td>
                            <td>{{ $currency }} {{ number_format($row['cash_out'], 0) }}</td>
                            <td>{{ $currency }} {{ number_format($row['outstanding'], 0) }}</td>
                            <td><span class="badge {{ $row['projected'] >= 0 ? 'success' : 'danger' }}">{{ $currency }} {{ number_format($row['projected'], 0) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-row">No projects</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
