@php use App\Filament\Resources\PositionResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />',
        'title' => 'Positions',
        'subtitle' => 'Job titles, grades, and role definitions by department',
    ])

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">Position List</div>
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
                    <th>Department</th>
                    <th>Level</th>
                    <th>Staff</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $position)
                    @php
                        $color = match ($position->level) {
                            'Executive' => 'danger',
                            'Management' => 'warning',
                            'Senior' => 'info',
                            default => 'gray',
                        };
                    @endphp
                    <tr>
                        <td>
                            <a class="row-title" href="{{ PositionResource::getUrl('edit', ['record' => $position->id]) }}">{{ $position->title }}</a>
                        </td>
                        <td>{{ $position->department?->name ?? '—' }}</td>
                        <td><span class="badge {{ $color }}">{{ $position->level ?? '—' }}</span></td>
                        <td>{{ $position->staff()->count() }}</td>
                        <td>
                            <div class="actions">
                                <a class="action-btn edit" title="Edit" href="{{ PositionResource::getUrl('edit', ['record' => $position->id]) }}">✎</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-row">No positions found</td>
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
