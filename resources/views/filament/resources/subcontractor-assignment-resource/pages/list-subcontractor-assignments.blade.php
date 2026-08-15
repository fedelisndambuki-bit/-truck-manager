@php use App\Filament\Resources\SubcontractorAssignmentResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.native-table-styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.5 4.5H4a2.25 2.25 0 01-2.25-2.25V6a2.25 2.25 0 012.25-2.25h15.5A2.25 2.25 0 0122 6v11.25a2.25 2.25 0 01-2.25 2.25h-.5z" />',
        'title' => 'Project Assignments',
        'subtitle' => 'Assign subcontractors to projects, sites, and phases',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Assignments</div>
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
