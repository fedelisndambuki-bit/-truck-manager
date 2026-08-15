@php use App\Filament\Resources\DailySiteDiaryResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />',
        'title' => 'Daily Site Diary',
        'subtitle' => 'Daily logs of weather, staffing and site progress',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Diary Entries</div>
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
                    <th>Weather</th>
                    <th>Workers</th>
                    <th>Logged By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $diary)
                    <tr>
                        <td>
                            <a class="row-title" href="{{ DailySiteDiaryResource::getUrl('edit', ['record' => $diary->id]) }}">{{ $diary->site?->name }}</a>
                        </td>
                        <td>{{ $diary->date?->format('d M Y') }}</td>
                        <td>{{ $diary->weather ?? '—' }}</td>
                        <td>{{ $diary->workers_on_site }}</td>
                        <td>{{ $diary->loggedBy?->full_name ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a class="action-btn edit" title="Edit" href="{{ DailySiteDiaryResource::getUrl('edit', ['record' => $diary->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-row">No diary entries found</td>
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
