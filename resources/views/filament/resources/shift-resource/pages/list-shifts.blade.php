@php use App\Filament\Resources\ShiftResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'title' => 'Shift Management',
        'subtitle' => 'Define work shifts and rotational patterns',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Shift List</div>
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
                <tr><th>Name</th><th>Type</th><th>Start</th><th>End</th><th>Attendance Records</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($records as $shift)
                    <tr>
                        <td><a class="row-title" href="{{ ShiftResource::getUrl('edit', ['record' => $shift->id]) }}">{{ $shift->name }}</a></td>
                        <td><span class="badge gray">{{ $shift->type }}</span></td>
                        <td>{{ $shift->start_time?->format('H:i') ?? '—' }}</td>
                        <td>{{ $shift->end_time?->format('H:i') ?? '—' }}</td>
                        <td>{{ $shift->attendances()->count() }}</td>
                        <td>
                            <div class="actions">
                                <a class="action-btn edit" title="Edit" href="{{ ShiftResource::getUrl('edit', ['record' => $shift->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-row">No shifts found</td></tr>
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
