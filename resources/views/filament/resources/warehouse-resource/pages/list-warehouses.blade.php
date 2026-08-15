@php use App\Filament\Resources\WarehouseResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72l1.189-1.19A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72" />',
        'title' => 'Warehouses',
        'subtitle' => 'Central, project, site, and mobile storage locations',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Warehouse List</div>
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
                <tr><th>Name</th><th>Type</th><th>Location</th><th>Manager</th><th>Stock Value</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($records as $warehouse)
                    <tr>
                        <td><a class="row-title" href="{{ WarehouseResource::getUrl('edit', ['record' => $warehouse->id]) }}">{{ $warehouse->name }}</a></td>
                        <td><span class="badge gray">{{ $warehouse->type }}</span></td>
                        <td>{{ $warehouse->location ?? '—' }}</td>
                        <td>{{ $warehouse->manager?->full_name ?? 'Unassigned' }}</td>
                        <td>{{ session('currency', 'KES') }} {{ number_format($warehouse->current_stock_value, 2) }}</td>
                        <td><span class="badge {{ $warehouse->status === 'Active' ? 'success' : 'gray' }}">{{ $warehouse->status }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="action-btn edit" title="Edit" href="{{ WarehouseResource::getUrl('edit', ['record' => $warehouse->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-row">No warehouses found</td></tr>
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
