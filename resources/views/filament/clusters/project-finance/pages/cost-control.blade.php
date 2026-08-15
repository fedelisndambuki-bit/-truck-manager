@php
    $currency = session('currency', 'KES');
    $rows = $this->getRows();
    $companyBreakdown = $this->getCompanyBreakdown();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />',
        'title' => 'Cost Control',
        'subtitle' => 'Costs automatically captured from Labour, Materials, Equipment, Subcontractors, Procurement, Site Expenses, Daily Site Diary and Quality Control',
    ])

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        @foreach ($companyBreakdown as $label => $amount)
            <x-filament::section>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $label }}</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $currency }} {{ number_format($amount, 0) }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Cost by Project</div>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Labour</th>
                        <th>Materials</th>
                        <th>Equipment</th>
                        <th>Subcontractors</th>
                        <th>Procurement</th>
                        <th>Site Exp.</th>
                        <th>Utilities</th>
                        <th>Transport</th>
                        <th>Overheads</th>
                        <th>Delay Impact</th>
                        <th>Quality Rework</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="row-title">{{ $row['project']->name }}</td>
                            @foreach ($row['breakdown'] as $amount)
                                <td>{{ $currency }} {{ number_format($amount, 0) }}</td>
                            @endforeach
                            <td><strong>{{ $currency }} {{ number_format($row['total'], 0) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="empty-row">No cost data captured yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
