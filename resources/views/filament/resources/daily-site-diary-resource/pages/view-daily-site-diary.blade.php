@php
    $diary = $record;
    $statusColors = [
        'Draft' => 'gray', 'Submitted' => 'warning', 'Reviewed' => 'info',
        'Approved' => 'success', 'Rejected' => 'danger', 'Locked' => 'gray',
    ];
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />',
        'title' => $diary->diary_number . ' — ' . ($diary->site?->name ?? 'No site'),
        'subtitle' => ($diary->date?->format('d M Y') ?? '') . ' · ' . ($diary->project?->name ?? 'No project'),
    ])

    <div x-data="{ activeTab: 'overview' }">
        <div class="tab-bar">
            @foreach ([
                'overview' => 'Overview', 'weather' => 'Weather', 'labour' => 'Labour', 'equipment' => 'Equipment',
                'materials' => 'Materials', 'subcontractors' => 'Subcontractors', 'activities' => 'Activities',
                'delays' => 'Delays', 'visitors' => 'Visitors', 'photos' => 'Photos', 'documents' => 'Documents',
                'approvals' => 'Approvals', 'reports' => 'Reports',
            ] as $key => $label)
                <div class="tab" :class="{ 'active': activeTab === '{{ $key }}' }" @click="activeTab = '{{ $key }}'">{{ $label }}</div>
            @endforeach
        </div>

        {{-- OVERVIEW --}}
        <div x-show="activeTab === 'overview'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card">
                    <div class="ov-label">Status</div>
                    <div class="mt-1"><x-filament::badge :color="$statusColors[$diary->status] ?? 'gray'">{{ $diary->status }}</x-filament::badge></div>
                </div>
                <div class="ov-card"><div class="ov-label">Supervisor</div><div class="ov-value" style="font-size:14px;">{{ $diary->loggedBy?->full_name ?? '—' }}</div></div>
                <div class="ov-card"><div class="ov-label">Weather</div><div class="ov-value" style="font-size:14px;">{{ $diary->conditions ?? $diary->weather ?? 'Not logged' }}</div></div>
                <div class="ov-card"><div class="ov-label">Progress</div><div class="ov-value">{{ $diary->progress_pct ?? 0 }}%</div></div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card"><div class="ov-label">Workers Present</div><div class="ov-value">{{ $diary->workers_on_site }}</div></div>
                <div class="ov-card"><div class="ov-label">Equipment Active</div><div class="ov-value">{{ $diary->equipment_active_count }}</div></div>
                <div class="ov-card"><div class="ov-label">Material Deliveries</div><div class="ov-value">{{ $diary->material_deliveries_count }}</div></div>
                <div class="ov-card"><div class="ov-label">Subcontractors</div><div class="ov-value">{{ $diary->subcontractors_on_site_count }}</div></div>
            </div>

            @if ($diary->work_summary)
                <div class="table-section" style="padding:20px;">
                    <div class="ov-label">Work Summary</div>
                    <p class="mt-2 text-sm text-gray-700">{{ $diary->work_summary }}</p>
                </div>
            @endif

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="table-section" style="padding:20px;">
                    <div class="ov-label">Supervisor Notes</div>
                    <dl class="mt-2 space-y-2 text-sm">
                        <div><dt class="font-semibold text-gray-600">General Observations</dt><dd>{{ $diary->general_observations ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-gray-600">Challenges</dt><dd>{{ $diary->challenges ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-gray-600">Risks</dt><dd>{{ $diary->risks ?? '—' }}</dd></div>
                    </dl>
                </div>
                <div class="table-section" style="padding:20px;">
                    <div class="ov-label">Planning</div>
                    <dl class="mt-2 space-y-2 text-sm">
                        <div><dt class="font-semibold text-gray-600">Recommendations</dt><dd>{{ $diary->recommendations ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-gray-600">Next Day Plan</dt><dd>{{ $diary->next_day_plan ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-gray-600">Additional Comments</dt><dd>{{ $diary->additional_comments ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- WEATHER --}}
        <div x-show="activeTab === 'weather'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card"><div class="ov-label">Conditions</div><div class="ov-value" style="font-size:16px;">{{ $diary->conditions ?? '—' }}</div></div>
                <div class="ov-card"><div class="ov-label">Temp High / Low</div><div class="ov-value" style="font-size:16px;">{{ $diary->temperature_high ?? '—' }}° / {{ $diary->temperature_low ?? '—' }}°</div></div>
                <div class="ov-card"><div class="ov-label">Rainfall</div><div class="ov-value">{{ $diary->rainfall_mm ?? 0 }} mm</div></div>
                <div class="ov-card"><div class="ov-label">Wind Speed</div><div class="ov-value">{{ $diary->wind_speed_kph ?? 0 }} kph</div></div>
                <div class="ov-card"><div class="ov-label">Humidity</div><div class="ov-value">{{ $diary->humidity_pct ?? 0 }}%</div></div>
                <div class="ov-card"><div class="ov-label">Weather Impact</div><div class="ov-value" style="font-size:16px;">{{ $diary->weather_impact ?? 'None' }}</div></div>
                <div class="ov-card"><div class="ov-label">Source</div><div class="ov-value" style="font-size:16px;">{{ ucfirst($diary->weather_source ?? 'manual') }}</div></div>
                <div class="ov-card"><div class="ov-label">Fetched At</div><div class="ov-value" style="font-size:14px;">{{ $diary->weather_fetched_at?->format('d M Y H:i') ?? '—' }}</div></div>
            </div>
            @if ($diary->weather)
                <div class="table-section" style="padding:20px;">
                    <div class="ov-label">Comments</div>
                    <p class="mt-2 text-sm text-gray-700">{{ $diary->weather }}</p>
                </div>
            @endif
        </div>

        {{-- LABOUR --}}
        <div x-show="activeTab === 'labour'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card"><div class="ov-label">Present</div><div class="ov-value">{{ $diary->workers_on_site }}</div></div>
                <div class="ov-card"><div class="ov-label">Absent</div><div class="ov-value">{{ $diary->workers_absent }}</div></div>
                <div class="ov-card"><div class="ov-label">Late</div><div class="ov-value">{{ $diary->workers_late }}</div></div>
                <div class="ov-card"><div class="ov-label">Overtime</div><div class="ov-value">{{ $diary->workers_overtime }}</div></div>
            </div>
        </div>

        {{-- EQUIPMENT --}}
        <div x-show="activeTab === 'equipment'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card"><div class="ov-label">Active</div><div class="ov-value">{{ $diary->equipment_active_count }}</div></div>
                <div class="ov-card"><div class="ov-label">Idle</div><div class="ov-value">{{ $diary->equipment_idle_count }}</div></div>
                <div class="ov-card"><div class="ov-label">Breakdowns</div><div class="ov-value">{{ $diary->equipment_breakdown_count }}</div></div>
                <div class="ov-card"><div class="ov-label">Utilization</div><div class="ov-value">{{ $diary->equipment_utilization_pct ?? 0 }}%</div></div>
            </div>
        </div>

        {{-- MATERIALS --}}
        <div x-show="activeTab === 'materials'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card"><div class="ov-label">Deliveries</div><div class="ov-value">{{ $diary->material_deliveries_count }}</div></div>
                <div class="ov-card"><div class="ov-label">Shortages</div><div class="ov-value">{{ $diary->material_shortages_count }}</div></div>
            </div>
        </div>

        {{-- SUBCONTRACTORS --}}
        <div x-show="activeTab === 'subcontractors'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card"><div class="ov-label">On Site</div><div class="ov-value">{{ $diary->subcontractors_on_site_count }}</div></div>
            </div>
            @if ($diary->subcontractor_progress_notes)
                <div class="table-section" style="padding:20px;">
                    <div class="ov-label">Progress Notes</div>
                    <p class="mt-2 text-sm text-gray-700">{{ $diary->subcontractor_progress_notes }}</p>
                </div>
            @endif
        </div>

        {{-- ACTIVITIES --}}
        <div x-show="activeTab === 'activities'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Activities Completed</div></div>
            <table>
                <thead><tr><th>Task</th><th>Area</th><th>Start</th><th>Finish</th><th>Completion %</th><th>Comments</th></tr></thead>
                <tbody>
                    @forelse ($this->getActivities() as $activity)
                        <tr>
                            <td>{{ $activity->task }}</td>
                            <td>{{ $activity->area ?? '—' }}</td>
                            <td>{{ $activity->start_time?->format('H:i') ?? '—' }}</td>
                            <td>{{ $activity->finish_time?->format('H:i') ?? '—' }}</td>
                            <td>{{ $activity->completion_pct }}%</td>
                            <td>{{ $activity->comments ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">No activities recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- DELAYS --}}
        <div x-show="activeTab === 'delays'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Delays</div></div>
            <table>
                <thead><tr><th>Type</th><th>Reason</th><th>Duration</th><th>Impact</th><th>Responsible Party</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($this->getDelays() as $delay)
                        <tr>
                            <td>{{ $delay->type }}</td>
                            <td>{{ $delay->reason }}</td>
                            <td>{{ $delay->duration_hours }} hrs</td>
                            <td><span class="badge {{ match($delay->impact) { 'Critical','High' => 'danger', 'Medium' => 'warning', default => 'gray' } }}">{{ $delay->impact }}</span></td>
                            <td>{{ $delay->responsible_party ?? '—' }}</td>
                            <td><span class="badge {{ $delay->status === 'Resolved' ? 'success' : 'warning' }}">{{ $delay->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">No delays recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- VISITORS --}}
        <div x-show="activeTab === 'visitors'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Visitors</div></div>
            <table>
                <thead><tr><th>Visitor</th><th>Company</th><th>Purpose</th><th>Check In</th><th>Check Out</th><th>Host</th></tr></thead>
                <tbody>
                    @forelse ($this->getDiaryVisitors() as $visitor)
                        <tr>
                            <td>{{ $visitor->visitor_name }}</td>
                            <td>{{ $visitor->company ?? '—' }}</td>
                            <td>{{ $visitor->purpose ?? '—' }}</td>
                            <td>{{ $visitor->check_in?->format('H:i') ?? '—' }}</td>
                            <td>{{ $visitor->check_out?->format('H:i') ?? '—' }}</td>
                            <td>{{ $visitor->host?->full_name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">No visitors recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PHOTOS --}}
        <div x-show="activeTab === 'photos'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @forelse ($this->getPhotos() as $photo)
                    <div class="ov-card">
                        @if ($photo->file_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk($photo->disk ?? 'public')->url($photo->file_path) }}" class="w-full h-32 object-cover rounded-lg mb-2" alt="{{ $photo->title }}">
                        @endif
                        <div class="ov-label">{{ $photo->photo_category ?? 'Progress' }}</div>
                        <div class="text-sm font-semibold">{{ $photo->title }}</div>
                        @if ($photo->latitude && $photo->longitude)
                            <div class="text-xs text-gray-500 mt-1">{{ $photo->latitude }}, {{ $photo->longitude }}</div>
                        @endif
                        <div class="text-xs text-gray-400">{{ $photo->taken_at?->format('d M Y H:i') ?? $photo->created_at?->format('d M Y H:i') }}</div>
                    </div>
                @empty
                    <div class="empty-row">No photos uploaded.</div>
                @endforelse
            </div>
        </div>

        {{-- DOCUMENTS --}}
        <div x-show="activeTab === 'documents'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Documents</div></div>
            <table>
                <thead><tr><th>Title</th><th>Category</th><th>Uploaded By</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    @forelse ($this->getDocuments() as $document)
                        <tr>
                            <td>{{ $document->title }}</td>
                            <td><span class="badge gray">{{ $document->category }}</span></td>
                            <td>{{ $document->uploadedBy?->name ?? '—' }}</td>
                            <td>{{ $document->created_at?->format('d M Y') }}</td>
                            <td>
                                @if ($document->file_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk($document->disk ?? 'public')->url($document->file_path) }}" target="_blank" class="action-btn view">↗</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-row">No documents attached.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- APPROVALS --}}
        <div x-show="activeTab === 'approvals'" class="table-section" style="padding:20px;">
            <div class="table-title" style="color:#0F172A; margin-bottom:16px;">Approval Timeline</div>
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <dt class="font-semibold">Submitted</dt>
                    <dd>{{ $diary->submittedBy?->full_name ?? '—' }} @ {{ $diary->submitted_at?->format('d M Y H:i') ?? 'not yet' }}</dd>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <dt class="font-semibold">Reviewed</dt>
                    <dd>{{ $diary->reviewedBy?->full_name ?? '—' }} @ {{ $diary->reviewed_at?->format('d M Y H:i') ?? 'not yet' }}</dd>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <dt class="font-semibold">Approved</dt>
                    <dd>{{ $diary->approvedBy?->full_name ?? '—' }} @ {{ $diary->approved_at?->format('d M Y H:i') ?? 'not yet' }}</dd>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <dt class="font-semibold">Rejected</dt>
                    <dd>{{ $diary->rejectedBy?->full_name ?? '—' }} @ {{ $diary->rejected_at?->format('d M Y H:i') ?? 'not yet' }} @if($diary->rejection_reason) — {{ $diary->rejection_reason }} @endif</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-semibold">Locked</dt>
                    <dd>{{ $diary->locked_at?->format('d M Y H:i') ?? 'not yet' }}</dd>
                </div>
            </dl>
        </div>

        {{-- REPORTS --}}
        <div x-show="activeTab === 'reports'" class="table-section" style="padding:20px;">
            <p class="text-sm text-gray-600">Use the <strong>Print Diary</strong> button above to download this diary as a PDF.
                @if (class_exists(\App\Filament\Pages\DailySiteDiaryReports::class))
                    Or visit <a href="{{ \App\Filament\Pages\DailySiteDiaryReports::getUrl() }}" class="text-primary-600 underline">Daily Site Diary Reports</a> for weekly/monthly rollups and analytical reports.
                @endif
            </p>
        </div>
    </div>
</x-filament-panels::page>
