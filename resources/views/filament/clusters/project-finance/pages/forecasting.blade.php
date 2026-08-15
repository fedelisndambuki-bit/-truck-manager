@php
    $currency = session('currency', 'KES');
    $rows = $this->getRows();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
        'title' => 'Forecasting',
        'subtitle' => 'Estimate at completion, revenue forecast and earned-value indices per project',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Project Forecasts</div>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Budget (BAC)</th>
                        <th>Actual Cost</th>
                        <th>Estimate at Completion</th>
                        <th>CPI</th>
                        <th>SPI</th>
                        <th>Est. Completion</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="row-title">{{ $row['project']->name }}</td>
                            <td>{{ $currency }} {{ number_format($row['bac'], 0) }}</td>
                            <td>{{ $currency }} {{ number_format($row['ac'], 0) }}</td>
                            <td>{{ $currency }} {{ number_format($row['eac'], 0) }}</td>
                            <td><span class="badge {{ $row['cpi'] >= 1 ? 'success' : 'danger' }}">{{ number_format($row['cpi'], 2) }}</span></td>
                            <td><span class="badge {{ $row['spi'] >= 1 ? 'success' : 'warning' }}">{{ number_format($row['spi'], 2) }}</span></td>
                            <td>{{ $row['estimated_completion']?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <button wire:click="generateSnapshot({{ $row['project']->id }})" class="action-btn view" title="Generate snapshot">
                                    <x-filament::icon icon="heroicon-o-camera" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-row">No projects to forecast</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
