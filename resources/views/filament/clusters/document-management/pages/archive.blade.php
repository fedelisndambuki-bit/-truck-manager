@php
    $records = $this->getRecords();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />',
        'title' => 'Archive',
        'subtitle' => 'Archived documents — retained for compliance, no longer active in the library',
    ])

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search archived documents..."
               style="width:100%; max-width:360px; border:1px solid #e5e7eb; border-radius:8px; padding:8px 12px; font-size:13px;">
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Archived Documents</div>
            <div class="table-results">
                @if ($records->total())
                    Showing <strong>{{ $records->firstItem() }}-{{ $records->lastItem() }}</strong> of <strong>{{ $records->total() }}</strong>
                @else
                    No results
                @endif
            </div>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Doc #</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Project</th>
                        <th>Archived</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td class="row-title">{{ $record->document_number }}</td>
                            <td>{{ $record->title }}</td>
                            <td><span class="badge info">{{ $record->category }}</span></td>
                            <td>{{ $record->project?->name ?? '—' }}</td>
                            <td>{{ $record->updated_at->format('d M Y') }}</td>
                            <td>
                                <button wire:click="restore({{ $record->id }})" class="action-btn view" title="Restore">
                                    <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">No archived documents</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px;">
            {{ $records->links() }}
        </div>
    </div>
</x-filament-panels::page>
