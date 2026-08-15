@php use App\Filament\Resources\MaterialResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />',
        'title' => 'Materials',
        'subtitle' => 'Material master data, stock status, and inventory value',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Material List</div>
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
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>UOM</th>
                    <th>On Hand</th>
                    <th>Stock Status</th>
                    <th>Inventory Value</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $material)
                    @php
                        $stockColor = MaterialResource::getStockStatusColor($material->stock_status);
                        $statusColor = MaterialResource::getStatusColor($material->status);
                    @endphp
                    <tr>
                        <td>{{ $material->material_code }}</td>
                        <td>
                            <a class="row-title" href="{{ MaterialResource::getUrl('view', ['record' => $material->id]) }}">{{ $material->name }}</a>
                        </td>
                        <td>{{ $material->category?->name ?? '—' }}</td>
                        <td>{{ $material->unit }}</td>
                        <td>{{ number_format($material->quantity_on_hand, 2) }}</td>
                        <td><span class="badge {{ $stockColor }}">{{ $material->stock_status }}</span></td>
                        <td>{{ session('currency', 'KES') }} {{ number_format($material->inventory_value, 2) }}</td>
                        <td>{{ $material->preferredSupplier?->company_name ?? '—' }}</td>
                        <td><span class="badge {{ $statusColor }}">{{ $material->status }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="action-btn view" title="View" href="{{ MaterialResource::getUrl('view', ['record' => $material->id]) }}">👁</a>
                                <a class="action-btn edit" title="Edit" href="{{ MaterialResource::getUrl('edit', ['record' => $material->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="empty-row">No materials found</td>
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
