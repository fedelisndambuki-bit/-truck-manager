@php
    use App\Filament\Resources\SiteResource;
    $site = $record;
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />',
        'title' => $site->name,
        'subtitle' => $site->site_code . ' — ' . ($site->project?->name ?? 'No parent project'),
    ])

    <style>
        .tab-bar{display:flex;gap:2px;background:#F1F5F9;border-radius:10px;padding:4px;border:1px solid #E2E8F0;overflow-x:auto;margin-bottom:20px}
        .tab{padding:7px 14px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;color:#64748B;transition:all .15s ease;white-space:nowrap}
        .tab.active{background:#FFFFFF;color:#0F172A;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #E2E8F0}
        .ov-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px}
        .ov-label{font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
        .ov-value{font-size:20px;font-weight:700;color:#0F172A;margin-top:4px}
    </style>

    <div x-data="{ activeTab: 'overview' }">
        <div class="tab-bar">
            @foreach ([
                'overview' => 'Overview', 'labour' => 'Labour', 'equipment' => 'Equipment', 'materials' => 'Materials',
                'diary' => 'Daily Diary', 'visitors' => 'Visitors', 'deliveries' => 'Deliveries', 'safety' => 'Safety',
                'quality' => 'Quality', 'inspections' => 'Inspections', 'documents' => 'Documents', 'photos' => 'Photos',
                'finance' => 'Finance', 'reports' => 'Reports', 'map' => 'Map', 'settings' => 'Settings',
            ] as $key => $label)
                <div class="tab" :class="{ 'active': activeTab === '{{ $key }}' }" @click="activeTab = '{{ $key }}'">{{ $label }}</div>
            @endforeach
        </div>

        {{-- OVERVIEW --}}
        <div x-show="activeTab === 'overview'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div class="ov-card">
                    <div class="ov-label">Status</div>
                    <div class="mt-1"><x-filament::badge :color="\App\Filament\Resources\SiteResource::getStatusColor($site->status)">{{ $site->status }}</x-filament::badge></div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Current Phase</div>
                    <div class="ov-value">{{ $site->current_phase ?? '—' }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Site Manager</div>
                    <div class="ov-value" style="font-size:14px;">{{ $site->teamSlot('Site Manager')?->full_name ?? $site->siteManager?->full_name ?? '—' }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Current Weather</div>
                    <div class="ov-value" style="font-size:14px;">{{ $this->getLatestWeather() ?? 'Not logged' }}</div>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Progress</span>
                    <span>{{ $site->progress_percent }}%</span>
                </div>
                <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    @php $p = $site->progress_percent ?? 0; @endphp
                    <div class="h-full rounded-full {{ $p < 70 ? 'bg-success-500' : ($p < 90 ? 'bg-warning-500' : 'bg-danger-500') }}" style="width: {{ $p }}%"></div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card"><div class="ov-label">Workers Today</div><div class="ov-value">{{ $this->getAttendanceToday()->where('status', 'Present')->count() }}</div></div>
                <div class="ov-card"><div class="ov-label">Equipment</div><div class="ov-value">{{ $this->getEquipmentAllocations()->count() }}</div></div>
                <div class="ov-card"><div class="ov-label">Open Incidents</div><div class="ov-value">{{ $this->getSafetyIncidents()->whereIn('status', ['Open', 'Investigating'])->count() }}</div></div>
                <div class="ov-card"><div class="ov-label">On-Site Visitors</div><div class="ov-value">{{ $this->getVisitors()->whereNull('check_out_at')->count() }}</div></div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
                @livewire(\App\Filament\Widgets\SiteProgressChart::class, ['record' => $site], key('spc-'.$site->id))
                @livewire(\App\Filament\Widgets\SiteEquipmentUtilizationChart::class, ['record' => $site], key('seu-'.$site->id))
                @livewire(\App\Filament\Widgets\SiteLabourProductivityChart::class, ['record' => $site], key('slp-'.$site->id))
            </div>
        </div>

        {{-- LABOUR --}}
        <div x-show="activeTab === 'labour'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Labour</div></div>
            <table>
                <thead><tr><th>Employee</th><th>Role</th><th>Daily Rate</th><th>Present Today</th></tr></thead>
                <tbody>
                    @php $presentIds = $this->getAttendanceToday()->where('status', 'Present')->pluck('employee_id'); @endphp
                    @forelse ($this->getLabourers() as $l)
                        <tr>
                            <td>{{ $l->employee?->full_name }}</td>
                            <td><span class="badge gray">{{ $l->role_on_site }}</span></td>
                            <td>{{ session('currency', 'KES') }} {{ number_format($l->daily_rate ?? 0, 2) }}</td>
                            <td><span class="badge {{ $presentIds->contains($l->employee_id) ? 'success' : 'danger' }}">{{ $presentIds->contains($l->employee_id) ? 'Present' : 'Absent' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No labourers assigned.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- EQUIPMENT --}}
        <div x-show="activeTab === 'equipment'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Equipment</div></div>
            <table>
                <thead><tr><th>Equipment</th><th>Hours Today</th><th>Fuel Today (L)</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($this->getEquipmentAllocations() as $a)
                        <tr>
                            <td>{{ $a->equipment?->name }}</td>
                            <td>{{ $a->operating_hours_today }}</td>
                            <td>{{ $a->fuel_consumed_today }}</td>
                            <td><span class="badge {{ $a->equipment?->status === 'Under Maintenance' ? 'danger' : 'success' }}">{{ $a->equipment?->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No equipment assigned.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- MATERIALS --}}
        <div x-show="activeTab === 'materials'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Materials</div></div>
            <table>
                <thead><tr><th>Material</th><th>Quantity</th><th>Status</th><th>On Hand</th></tr></thead>
                <tbody>
                    @forelse ($this->getMaterialDeliveries() as $d)
                        <tr>
                            <td>{{ $d->material?->name }}</td>
                            <td>{{ $d->quantity }}</td>
                            <td><span class="badge {{ $d->status === 'Delivered' ? 'success' : 'warning' }}">{{ $d->status }}</span></td>
                            <td>{{ $d->material?->quantity_on_hand }} @if($d->material && $d->material->quantity_on_hand < $d->material->reorder_level)<span class="badge danger">Low Stock</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No material records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- DAILY DIARY --}}
        <div x-show="activeTab === 'diary'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Daily Site Diary</div></div>
            <table>
                <thead><tr><th>Date</th><th>Weather</th><th>Workers</th><th>Summary</th></tr></thead>
                <tbody>
                    @forelse ($this->getDiaries() as $d)
                        <tr><td>{{ $d->date?->format('d M Y') }}</td><td>{{ $d->weather ?? '—' }}</td><td>{{ $d->workers_on_site }}</td><td>{{ \Illuminate\Support\Str::limit($d->work_summary, 60) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No diary entries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- VISITORS --}}
        <div x-show="activeTab === 'visitors'">
            <div class="mb-3 flex justify-end">{{ $this->checkInVisitorAction() }}</div>
            <div class="table-section">
                <div class="table-header-custom"><div class="table-title">Visitors</div></div>
                <table>
                    <thead><tr><th>Name</th><th>Company</th><th>Purpose</th><th>Host</th><th>Check-In</th><th>Check-Out</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($this->getVisitors() as $v)
                            <tr>
                                <td>{{ $v->name }}</td>
                                <td>{{ $v->company_name ?? '—' }}</td>
                                <td>{{ $v->purpose ?? '—' }}</td>
                                <td>{{ $v->host?->full_name ?? '—' }}</td>
                                <td>{{ $v->check_in_at?->format('d M Y H:i') }}</td>
                                <td>{{ $v->check_out_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td>
                                    @if (!$v->check_out_at)
                                        <button type="button" class="action-btn edit" wire:click="checkOutVisitor({{ $v->id }})" title="Check Out">⏻</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty-row">No visitors logged.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- DELIVERIES --}}
        <div x-show="activeTab === 'deliveries'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Deliveries</div></div>
            <table>
                <thead><tr><th>Material</th><th>Supplier</th><th>Vehicle</th><th>Driver</th><th>Arrival</th><th>Offloading</th><th>Inspection</th><th>Received By</th></tr></thead>
                <tbody>
                    @forelse ($this->getMaterialDeliveries() as $d)
                        <tr>
                            <td>{{ $d->material?->name }}</td>
                            <td>{{ $d->supplier_name ?? '—' }}</td>
                            <td>{{ $d->vehicle ?? '—' }}</td>
                            <td>{{ $d->driver_name ?? '—' }}</td>
                            <td>{{ $d->arrival_time?->format('d M Y H:i') ?? '—' }}</td>
                            <td>{{ $d->offloading_time?->format('d M Y H:i') ?? '—' }}</td>
                            <td><span class="badge {{ $d->inspection_status === 'Passed' ? 'success' : ($d->inspection_status === 'Failed' ? 'danger' : 'gray') }}">{{ $d->inspection_status ?? 'Pending' }}</span></td>
                            <td>{{ $d->receivedBy?->full_name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-row">No deliveries logged.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- SAFETY --}}
        <div x-show="activeTab === 'safety'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Safety Incidents</div></div>
            <table>
                <thead><tr><th>Date</th><th>Severity</th><th>Status</th><th>Description</th></tr></thead>
                <tbody>
                    @forelse ($this->getSafetyIncidents() as $i)
                        <tr>
                            <td>{{ $i->date?->format('d M Y') }}</td>
                            <td><span class="badge {{ in_array($i->severity, ['High','Critical']) ? 'danger' : 'warning' }}">{{ $i->severity }}</span></td>
                            <td>{{ $i->status }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($i->description, 60) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No safety incidents.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- QUALITY --}}
        <div x-show="activeTab === 'quality'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Quality Checks</div></div>
            <table>
                <thead><tr><th>Item</th><th>Date</th><th>Result</th></tr></thead>
                <tbody>
                    @forelse ($this->getQualityInspections() as $q)
                        <tr><td>{{ $q->checklist_item ?? '—' }}</td><td>{{ $q->date?->format('d M Y') }}</td><td><span class="badge {{ $q->result === 'Pass' ? 'success' : 'danger' }}">{{ $q->result }}</span></td></tr>
                    @empty
                        <tr><td colspan="3" class="empty-row">No quality checks.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- INSPECTIONS --}}
        <div x-show="activeTab === 'inspections'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Site Inspections</div></div>
            <table>
                <thead><tr><th>Inspector</th><th>Type</th><th>Date</th><th>Result</th></tr></thead>
                <tbody>
                    @forelse ($this->getSiteInspections() as $i)
                        <tr>
                            <td>{{ $i->inspector?->full_name ?? '—' }}</td>
                            <td><span class="badge gray">{{ $i->type }}</span></td>
                            <td>{{ $i->date?->format('d M Y') }}</td>
                            <td><span class="badge {{ $i->result === 'Pass' ? 'success' : ($i->result === 'Fail' ? 'danger' : 'warning') }}">{{ $i->result }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No inspections logged.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- DOCUMENTS --}}
        <div x-show="activeTab === 'documents'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Documents</div></div>
            <table>
                <thead><tr><th>Title</th><th>Category</th><th>Uploaded</th><th></th></tr></thead>
                <tbody>
                    @forelse ($this->getDocuments() as $doc)
                        <tr>
                            <td>{{ $doc->title }}</td>
                            <td><span class="badge gray">{{ $doc->category }}</span></td>
                            <td>{{ $doc->created_at->format('d M Y') }}</td>
                            <td><a class="action-btn view" href="{{ \Illuminate\Support\Facades\Storage::disk($doc->disk)->url($doc->file_path) }}" target="_blank">⬇</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No documents uploaded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PHOTOS --}}
        <div x-show="activeTab === 'photos'">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @forelse ($this->getPhotos() as $photo)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk($photo->disk)->url($photo->file_path) }}" target="_blank" class="block overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk($photo->disk)->url($photo->file_path) }}" alt="{{ $photo->title }}" class="h-32 w-full object-cover">
                    </a>
                @empty
                    <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">No photos uploaded yet.</p>
                @endforelse
            </div>
        </div>

        {{-- FINANCE --}}
        <div x-show="activeTab === 'finance'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div class="ov-card"><div class="ov-label">Subcontractor Contracts</div><div class="ov-value">{{ $this->getSubcontractors()->count() }}</div></div>
                <div class="ov-card"><div class="ov-label">Contract Value</div><div class="ov-value">{{ session('currency', 'KES') }} {{ number_format($this->getSubcontractors()->sum('contract_value'), 0) }}</div></div>
                <div class="ov-card"><div class="ov-label">Parent Project Budget</div><div class="ov-value">{{ session('currency', 'KES') }} {{ number_format($site->project?->budget ?? 0, 0) }}</div></div>
            </div>
        </div>

        {{-- REPORTS --}}
        <div x-show="activeTab === 'reports'">
            <p class="text-sm text-gray-500 dark:text-gray-400">Use "Print Summary" in the header above to generate this site's PDF report.</p>
        </div>

        {{-- MAP --}}
        <div x-show="activeTab === 'map'">
            <div class="mb-3 flex justify-end">{{ $this->addZoneAction() }}</div>
            @livewire(\App\Filament\Widgets\SiteZoneMapWidget::class, ['record' => $site], key('szm-'.$site->id))
        </div>

        {{-- SETTINGS --}}
        <div x-show="activeTab === 'settings'">
            <x-filament::section>
                <x-slot name="heading">Site Settings</x-slot>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                    <div><dt class="text-gray-500">Working Days</dt><dd>{{ $site->working_days ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Working Hours</dt><dd>{{ $site->working_hours ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Shift Pattern</dt><dd>{{ $site->shift_pattern ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Night Shift</dt><dd>{{ $site->night_shift_enabled ? 'Enabled' : 'Disabled' }}</dd></div>
                    <div><dt class="text-gray-500">Max Workforce</dt><dd>{{ $site->max_workforce ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Power Source</dt><dd>{{ $site->power_source ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Water Source</dt><dd>{{ $site->water_source ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Safety Risk Level</dt><dd>{{ $site->safety_risk_level ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Emergency Assembly Point</dt><dd>{{ $site->emergency_assembly_point ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Nearest Hospital</dt><dd>{{ $site->nearest_hospital ?? '—' }}</dd></div>
                </dl>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
