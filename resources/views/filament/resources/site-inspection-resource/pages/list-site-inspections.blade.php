@php use App\Filament\Resources\SiteInspectionResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'title' => 'Site Inspections',
        'subtitle' => 'Record and review routine site inspections',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Inspection List</div>
            <div class="table-results">
                @php $records = $this->getTable()->getRecords(); @endphp
                @if ($records->firstItem() && $records->lastItem() && $records->total())
                    Showing <strong>{{ $records->firstItem() }}-{{ $records->lastItem() }}</strong> of <strong>{{ $records->total() }}</strong> results
                @else
                    No results
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Site</th>
                    <th>Inspector</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Result</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $inspection)
                    @php
                        $color = match ($inspection->result) {
                            'Pass' => 'success',
                            'Fail' => 'danger',
                            'Needs Review' => 'warning',
                            default => 'gray',
                        };
                    @endphp
                    <tr>
                        <td>
                            <a class="row-title" href="{{ SiteInspectionResource::getUrl('edit', ['record' => $inspection->id]) }}">{{ $inspection->site?->name }}</a>
                        </td>
                        <td>{{ $inspection->inspector?->full_name ?? '—' }}</td>
                        <td>{{ $inspection->date?->format('d M Y') }}</td>
                        <td><span class="badge gray">{{ $inspection->type }}</span></td>
                        <td><span class="badge {{ $color }}">{{ $inspection->result }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="action-btn edit" title="Edit" href="{{ SiteInspectionResource::getUrl('edit', ['record' => $inspection->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-row">No inspections found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($records->hasPages())
            <div style="padding:20px 25px;border-top:1px solid #f3f4f6;">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
