@php use App\Filament\Resources\SafetyIncidentResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />',
        'title' => 'Safety & Incidents',
        'subtitle' => 'Log and track site safety incidents',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Incident List</div>
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
                    <th>Date</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $incident)
                    @php
                        $severityColor = match ($incident->severity) {
                            'Low' => 'success',
                            'Medium' => 'warning',
                            'High', 'Critical' => 'danger',
                            default => 'gray',
                        };
                        $statusColor = match ($incident->status) {
                            'Open' => 'danger',
                            'Investigating' => 'warning',
                            'Closed' => 'success',
                            default => 'gray',
                        };
                    @endphp
                    <tr>
                        <td>
                            <a class="row-title" href="{{ SafetyIncidentResource::getUrl('edit', ['record' => $incident->id]) }}">{{ $incident->site?->name }}</a>
                        </td>
                        <td>{{ $incident->date?->format('d M Y') }}</td>
                        <td><span class="badge {{ $severityColor }}">{{ $incident->severity }}</span></td>
                        <td><span class="badge {{ $statusColor }}">{{ $incident->status }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="action-btn edit" title="Edit" href="{{ SafetyIncidentResource::getUrl('edit', ['record' => $incident->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-row">No incidents found</td>
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
