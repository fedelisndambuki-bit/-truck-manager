<x-filament::section class="filament-filter-section-wrapper">
    <div class="filter-header">
        <div class="filter-title">
            <i class="fas fa-filter"></i>
            Filter & Reports
        </div>
        <div class="export-buttons">
            <button type="button" wire:click="export('excel')" class="btn-export excel">
                <i class="fas fa-file-excel"></i>
                Export to Excel
            </button>
            <button type="button" wire:click="export('pdf')" class="btn-export pdf">
                <i class="fas fa-file-pdf"></i>
                Export to PDF
            </button>
        </div>
    </div>
    
    {{ $this->form }}
</x-filament::section>
