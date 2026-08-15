<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />',
        'title' => static::$title,
        'subtitle' => 'Export any data source directly in PDF, CSV, Excel, Word or JSON — every export is logged in Saved Reports.',
    ])

    <x-filament::section>
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
            <div class="w-full sm:w-72">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Data Source</label>
                <select wire:model.live="dataSource" class="fi-select-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    @foreach ($this->getDataSources() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap gap-2">
                <button wire:click="exportPdf" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-9 pl-3 pr-4 text-sm inline-grid gap-1 shadow-sm bg-danger-600 text-white hover:bg-danger-500">PDF</button>
                <button wire:click="exportCsv" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-9 pl-3 pr-4 text-sm inline-grid gap-1 shadow-sm bg-gray-600 text-white hover:bg-gray-500">CSV</button>
                <button wire:click="exportExcel" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-9 pl-3 pr-4 text-sm inline-grid gap-1 shadow-sm bg-success-600 text-white hover:bg-success-500">Excel</button>
                <button wire:click="exportWord" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-9 pl-3 pr-4 text-sm inline-grid gap-1 shadow-sm bg-info-600 text-white hover:bg-info-500">Word</button>
                <button wire:click="exportJson" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-9 pl-3 pr-4 text-sm inline-grid gap-1 shadow-sm bg-primary-600 text-white hover:bg-primary-500">JSON</button>
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Exports the first 500 records of the selected data source, scoped to your company.</p>
    </x-filament::section>
</x-filament-panels::page>
