<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.native-table-styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm0 0H12m-8.25 0h16.5M4.5 12h15M4.5 17.25h15" />',
        'title' => 'Meeting Minutes',
        'subtitle' => 'Site and project meeting records',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Meeting Minutes</div>
            <div class="table-results">
                @php $records = $this->getTable()->getRecords(); @endphp
                @if ($records->firstItem() && $records->lastItem() && $records->total())
                    Showing <strong>{{ $records->firstItem() }}-{{ $records->lastItem() }}</strong> of <strong>{{ $records->total() }}</strong> results
                @else
                    No results
                @endif
            </div>
        </div>

        <div class="tanaki-native-table">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
