<x-filament::section class="filter-section-wrapper">
    <div class="filter-header">
        <div class="filter-title">
            <x-filament::icon icon="heroicon-m-funnel" class="h-5 w-5" />
            Filter & Reports
        </div>
        <div class="export-buttons">
            <button type="button" wire:click="export('excel')" wire:loading.attr="disabled" class="btn-export excel">
                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-5 w-5" />
                Export to Excel
            </button>
            <button type="button" wire:click="export('pdf')" wire:loading.attr="disabled" class="btn-export pdf">
                <x-filament::icon icon="heroicon-m-document-arrow-down" class="h-5 w-5" />
                Export to PDF
            </button>
        </div>
    </div>
    
    {{ $this->form }}
</x-filament::section>
