@php
    $rows = $this->getRows();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.native-table-styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />',
        'title' => 'Revisions',
        'subtitle' => 'Unified version history across documents and drawings',
    ])

    <div class="table-section">
        <div class="table-header-custom"><div class="table-title">Recent Revisions</div></div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Version</th>
                        <th>Revision #</th>
                        <th>Change Notes</th>
                        <th>Uploaded By</th>
                        <th>Current</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><span class="badge info">{{ $row['entity_label'] }}</span></td>
                            <td class="row-title">{{ $row['entity_title'] }}</td>
                            <td>{{ $row['version']->version_number }}</td>
                            <td>{{ $row['version']->revision_number }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($row['version']->change_notes, 40) }}</td>
                            <td>{{ $row['version']->uploadedBy?->name ?? '—' }}</td>
                            <td><span class="badge {{ $row['version']->is_current ? 'success' : 'gray' }}">{{ $row['version']->is_current ? 'Current' : 'Superseded' }}</span></td>
                            <td>{{ $row['version']->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-row">No revisions recorded yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
