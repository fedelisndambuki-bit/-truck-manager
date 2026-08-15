@php use App\Filament\Resources\SupplierResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.001" />',
        'title' => 'Suppliers',
        'subtitle' => 'Materials suppliers, performance, and contracts',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Supplier List</div>
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
                <tr><th>Company</th><th>Contact</th><th>Phone</th><th>Rating</th><th>Materials</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($records as $supplier)
                    <tr>
                        <td><a class="row-title" href="{{ SupplierResource::getUrl('view', ['record' => $supplier->id]) }}">{{ $supplier->company_name }}</a></td>
                        <td>{{ $supplier->contact_person ?? '—' }}</td>
                        <td>{{ $supplier->phone ?? '—' }}</td>
                        <td>{{ $supplier->performance_rating ?? '—' }} / 5</td>
                        <td>{{ $supplier->materials()->count() }}</td>
                        <td>
                            @php
                                $color = match ($supplier->status) {
                                    'Active' => 'success',
                                    'Blacklisted' => 'danger',
                                    default => 'gray',
                                };
                            @endphp
                            <span class="badge {{ $color }}">{{ $supplier->status }}</span>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="action-btn view" title="View" href="{{ SupplierResource::getUrl('view', ['record' => $supplier->id]) }}">👁</a>
                                <a class="action-btn edit" title="Edit" href="{{ SupplierResource::getUrl('edit', ['record' => $supplier->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-row">No suppliers found</td></tr>
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
