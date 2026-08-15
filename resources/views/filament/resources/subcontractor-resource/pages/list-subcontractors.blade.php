@php use App\Filament\Resources\SubcontractorResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 .621-.504 1.125-1.125 1.125h-15c-.621 0-1.125-.504-1.125-1.125v-4.25m17.25 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />',
        'title' => 'Subcontractors',
        'subtitle' => 'Subcontractor master data, compliance, and performance',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Subcontractor List</div>
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
                    <th>Code</th><th>Company</th><th>Specialization</th><th>Contact</th>
                    <th>Compliance</th><th>Avg. Rating</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $sub)
                    @php
                        $statusColor = SubcontractorResource::getStatusColor($sub->status);
                        $complianceColor = $sub->compliance_status === 'Compliant' ? 'success' : 'danger';
                    @endphp
                    <tr>
                        <td>{{ $sub->subcontractor_code }}</td>
                        <td>
                            <a class="row-title" href="{{ SubcontractorResource::getUrl('view', ['record' => $sub->id]) }}">{{ $sub->company_name }}</a>
                        </td>
                        <td>{{ $sub->specialization?->name ?? '—' }}</td>
                        <td>{{ $sub->contact_person ?? '—' }}</td>
                        <td><span class="badge {{ $complianceColor }}">{{ $sub->compliance_status }}</span></td>
                        <td>{{ $sub->average_performance_rating ? round($sub->average_performance_rating, 1) . ' / 5' : '—' }}</td>
                        <td><span class="badge {{ $statusColor }}">{{ $sub->status }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="action-btn view" title="View" href="{{ SubcontractorResource::getUrl('view', ['record' => $sub->id]) }}">👁</a>
                                <a class="action-btn edit" title="Edit" href="{{ SubcontractorResource::getUrl('edit', ['record' => $sub->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-row">No subcontractors found</td>
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
