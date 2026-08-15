@php
    $results = $this->getResults();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
        'title' => $record->name,
        'subtitle' => 'Data source: ' . $record->data_source . ' — ' . count($results['rows']) . ' row(s)',
    ])

    <div class="flex flex-wrap gap-3 mb-6">
        <button wire:click="exportPdf" class="fi-btn rounded-lg h-9 px-4 text-sm font-semibold shadow-sm bg-primary-600 text-white hover:bg-primary-500 inline-flex items-center gap-1">
            <x-filament::icon icon="heroicon-o-document-arrow-down" class="h-4 w-4" /> Export PDF
        </button>
        <button wire:click="exportCsv" class="fi-btn rounded-lg h-9 px-4 text-sm font-semibold shadow-sm bg-gray-600 text-white hover:bg-gray-500 inline-flex items-center gap-1">
            <x-filament::icon icon="heroicon-o-document-arrow-down" class="h-4 w-4" /> Export CSV
        </button>
        <button wire:click="exportExcel" class="fi-btn rounded-lg h-9 px-4 text-sm font-semibold shadow-sm bg-success-600 text-white hover:bg-success-500 inline-flex items-center gap-1">
            <x-filament::icon icon="heroicon-o-document-arrow-down" class="h-4 w-4" /> Export Excel
        </button>
    </div>

    @if ($record->chart_type && !empty($results['chart_labels']))
        <div
            wire:ignore
            x-data="{
                init() {
                    const load = () => new Promise((resolve) => {
                        if (window.Chart) { resolve(); return; }
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4';
                        script.onload = () => resolve();
                        document.head.appendChild(script);
                    });
                    load().then(() => {
                        new Chart(this.$refs.canvas, {
                            type: '{{ $record->chart_type }}',
                            data: {
                                labels: @js($results['chart_labels']),
                                datasets: [{ label: '{{ $record->name }}', data: @js($results['chart_values']), backgroundColor: ['#2563eb','#10b981','#f59e0b','#8b5cf6','#ef4444','#64748b','#ec4899','#0891b2'] }],
                            },
                        });
                    });
                }
            }"
            class="mb-6 rounded-xl border border-gray-200 dark:border-white/10 p-4"
        >
            <canvas x-ref="canvas" height="90"></canvas>
        </div>
    @endif

    <div class="table-section">
        <div class="table-header-custom"><div class="table-title">Results</div></div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        @foreach ($results['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($results['columns']) }}" class="empty-row">No data matches this report definition</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
