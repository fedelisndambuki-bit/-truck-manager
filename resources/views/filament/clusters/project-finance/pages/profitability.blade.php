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
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m-4.5-3.5h9M6.75 6.75h10.5a2.25 2.25 0 012.25 2.25v6a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 15V9a2.25 2.25 0 012.25-2.25z" />',
        'title' => 'Profitability',
        'subtitle' => 'Profit, margin and earned-value performance per project',
    ])

    <div class="table-section">
        <div class="table-header-custom"><div class="table-title">Project Profitability</div></div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Revenue</th>
                        <th>Cost</th>
                        <th>Profit</th>
                        <th>Gross Margin</th>
                        <th>CPI</th>
                        <th>SPI</th>
                        <th>Cost Variance</th>
                        <th>Schedule Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="row-title">{{ $row['project']->name }}</td>
                            <td>{{ $currency }} {{ number_format($row['revenue'], 0) }}</td>
                            <td>{{ $currency }} {{ number_format($row['cost'], 0) }}</td>
                            <td><span class="badge {{ $row['profit'] >= 0 ? 'success' : 'danger' }}">{{ $currency }} {{ number_format($row['profit'], 0) }}</span></td>
                            <td>{{ $row['gross_margin'] }}%</td>
                            <td><span class="badge {{ $row['cpi'] >= 1 ? 'success' : 'danger' }}">{{ number_format($row['cpi'], 2) }}</span></td>
                            <td><span class="badge {{ $row['spi'] >= 1 ? 'success' : 'warning' }}">{{ number_format($row['spi'], 2) }}</span></td>
                            <td>{{ $currency }} {{ number_format($row['cv'], 0) }}</td>
                            <td>{{ $currency }} {{ number_format($row['sv'], 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-row">No projects</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
