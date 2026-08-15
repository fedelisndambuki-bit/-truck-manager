@php use App\Filament\Resources\DocumentResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
        'title' => 'Documents',
        'subtitle' => 'Blueprints, contracts, permits and reports',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Document List</div>
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
                    <th>Title</th>
                    <th>Project</th>
                    <th>Category</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $document)
                    @php
                        $color = match ($document->category) {
                            'Blueprint' => 'info',
                            'Contract' => 'gray',
                            'Permit' => 'warning',
                            'Inspection Report' => 'success',
                            'Safety Report' => 'danger',
                            'Invoice' => 'success',
                            default => 'gray',
                        };
                    @endphp
                    <tr>
                        <td>
                            <a class="row-title" href="{{ DocumentResource::getUrl('edit', ['record' => $document->id]) }}">{{ $document->title }}</a>
                        </td>
                        <td>{{ $document->project?->name ?? '—' }}</td>
                        <td><span class="badge {{ $color }}">{{ $document->category }}</span></td>
                        <td>{{ $document->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="actions">
                                <a class="action-btn view" title="Download" href="{{ \Illuminate\Support\Facades\Storage::disk($document->disk)->url($document->file_path) }}" target="_blank">⬇</a>
                                <a class="action-btn edit" title="Edit" href="{{ DocumentResource::getUrl('edit', ['record' => $document->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-row">No documents found</td>
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
