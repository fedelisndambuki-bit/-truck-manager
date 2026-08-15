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
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />',
        'title' => 'Revenue Recognition',
        'subtitle' => 'Earned, recognized and deferred contract revenue (percentage-of-completion)',
    ])

    <div class="table-section">
        <div class="table-header-custom"><div class="table-title">Revenue by Project</div></div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>% Complete</th>
                        <th>Contract Revenue</th>
                        <th>Earned Revenue</th>
                        <th>Recognized Revenue</th>
                        <th>Deferred Revenue</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="row-title">{{ $row['project']->name }}</td>
                            <td>{{ number_format($row['percent_complete'], 1) }}%</td>
                            <td>{{ $currency }} {{ number_format($row['contract_revenue'], 0) }}</td>
                            <td>{{ $currency }} {{ number_format($row['earned_revenue'], 0) }}</td>
                            <td>{{ $currency }} {{ number_format($row['recognized_revenue'], 0) }}</td>
                            <td><span class="badge {{ $row['deferred_revenue'] > 0 ? 'warning' : 'success' }}">{{ $currency }} {{ number_format($row['deferred_revenue'], 0) }}</span></td>
                            <td>
                                <button wire:click="recordPeriod({{ $row['project']->id }})" class="action-btn view" title="Record this period">
                                    <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-row">No projects</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
