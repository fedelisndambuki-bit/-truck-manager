@php use App\Filament\Resources\QualityInspectionResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'title' => 'Quality Control',
        'subtitle' => 'Track quality assurance checklists across sites',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Quality Check List</div>
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
                    <th>Checklist Item</th>
                    <th>Date</th>
                    <th>Result</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $qc)
                    @php $color = $qc->result === 'Pass' ? 'success' : 'danger'; @endphp
                    <tr>
                        <td>
                            <a class="row-title" href="{{ QualityInspectionResource::getUrl('edit', ['record' => $qc->id]) }}">{{ $qc->site?->name }}</a>
                        </td>
                        <td>{{ $qc->checklist_item ?? '—' }}</td>
                        <td>{{ $qc->date?->format('d M Y') }}</td>
                        <td><span class="badge {{ $color }}">{{ $qc->result }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="action-btn edit" title="Edit" href="{{ QualityInspectionResource::getUrl('edit', ['record' => $qc->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-row">No quality checks found</td>
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
