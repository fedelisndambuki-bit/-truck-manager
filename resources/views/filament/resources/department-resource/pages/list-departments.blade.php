@php use App\Filament\Resources\DepartmentResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />',
        'title' => 'Departments',
        'subtitle' => 'Organizational departments across the workforce',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Department List</div>
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
                    <th>Name</th>
                    <th>Code</th>
                    <th>Head</th>
                    <th>Positions</th>
                    <th>Staff</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $dept)
                    <tr>
                        <td>
                            <a class="row-title" href="{{ DepartmentResource::getUrl('edit', ['record' => $dept->id]) }}">{{ $dept->name }}</a>
                        </td>
                        <td>{{ $dept->code ?? '—' }}</td>
                        <td>{{ $dept->head?->full_name ?? 'Unassigned' }}</td>
                        <td>{{ $dept->positions()->count() }}</td>
                        <td>{{ $dept->staff()->count() }}</td>
                        <td>
                            <div class="actions">
                                <a class="action-btn edit" title="Edit" href="{{ DepartmentResource::getUrl('edit', ['record' => $dept->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-row">No departments found</td>
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
