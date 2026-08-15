@php
    use App\Filament\Resources\ProjectResource;
    $project = $record;
    $currency = session('currency', 'KES');
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />',
        'title' => $project->name,
        'subtitle' => $project->project_code . ' — ' . ($project->client?->company_name ?? 'No client'),
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
                'overview' => 'Overview', 'sites' => 'Sites', 'equipment' => 'Equipment', 'labour' => 'Labour',
                'materials' => 'Materials', 'subcontractors' => 'Subcontractors', 'finance' => 'Finance',
                'documents' => 'Documents', 'safety' => 'Safety', 'quality' => 'Quality', 'diary' => 'Daily Diary',
                'timeline' => 'Timeline', 'analytics' => 'Analytics', 'settings' => 'Settings',
            ] as $key => $label)
                <div class="tab" :class="{ 'active': activeTab === '{{ $key }}' }" @click="activeTab = '{{ $key }}'">{{ $label }}</div>
            @endforeach
        </div>

        {{-- OVERVIEW --}}
        <div x-show="activeTab === 'overview'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div class="ov-card">
                    <div class="ov-label">Status</div>
                    <div class="mt-1"><x-filament::badge :color="\App\Filament\Resources\ProjectResource::getStatusColor($project->status)">{{ $project->status }}</x-filament::badge></div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Current Phase</div>
                    <div class="ov-value">{{ $project->current_phase ?? '—' }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Risk Level</div>
                    <div class="mt-1"><x-filament::badge :color="\App\Filament\Resources\ProjectResource::getPriorityColor($project->risk_level ?? 'Low')">{{ $project->risk_level ?? 'Low' }}</x-filament::badge></div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Weather Monitoring</div>
                    <div class="ov-value">{{ $project->weather_monitoring_enabled ? 'Enabled' : 'Disabled' }}</div>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Progress</span>
                    <span>{{ $project->progress_percent }}%</span>
                </div>
                <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    @php $p = $project->progress_percent ?? 0; @endphp
                    <div class="h-full rounded-full {{ $p < 70 ? 'bg-success-500' : ($p < 90 ? 'bg-warning-500' : 'bg-danger-500') }}" style="width: {{ $p }}%"></div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Client</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Company</dt><dd>{{ $project->client?->company_name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Contact</dt><dd>{{ $project->client?->contact_person ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd>{{ $project->client?->phone ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>{{ $project->client?->email ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
                <x-filament::section>
                    <x-slot name="heading">Financial Summary</x-slot>
                    @php $fin = $this->getFinanceSummary(); @endphp
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Budget</dt><dd>{{ $currency }} {{ number_format($fin['budget'], 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Spent</dt><dd>{{ $currency }} {{ number_format($fin['spent'], 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Revenue</dt><dd>{{ $currency }} {{ number_format($fin['revenue'], 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Profit</dt><dd>{{ $currency }} {{ number_format($fin['profit'], 2) }}</dd></div>
                    </dl>
                </x-filament::section>
            </div>

            <x-filament::section class="mt-4">
                <x-slot name="heading">Upcoming Milestones</x-slot>
                <div class="space-y-2">
                    @forelse ($this->getMilestones()->whereNotIn('status', ['Completed'])->take(5) as $milestone)
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ $milestone->name }}</span>
                            <span class="text-gray-500">{{ $milestone->planned_date?->format('d M Y') ?? '—' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming milestones.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        {{-- SITES --}}
        <div x-show="activeTab === 'sites'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Sites</div></div>
            <table>
                <thead><tr><th>Name</th><th>Location</th><th>Site Manager</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($this->getSites() as $site)
                        <tr><td>{{ $site->name }}</td><td>{{ $site->location ?? '—' }}</td><td>{{ $site->siteManager?->full_name ?? '—' }}</td><td><span class="badge {{ $site->status === 'Active' ? 'success' : 'gray' }}">{{ $site->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No sites yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- EQUIPMENT --}}
        <div x-show="activeTab === 'equipment'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Equipment Allocations</div></div>
            <table>
                <thead><tr><th>Equipment</th><th>Site</th><th>Hours Today</th><th>Fuel Today (L)</th></tr></thead>
                <tbody>
                    @forelse ($this->getEquipmentAllocations() as $a)
                        <tr><td>{{ $a->equipment?->name }}</td><td>{{ $a->site?->name }}</td><td>{{ $a->operating_hours_today }}</td><td>{{ $a->fuel_consumed_today }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No equipment allocated.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- LABOUR --}}
        <div x-show="activeTab === 'labour'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Labour</div></div>
            <table>
                <thead><tr><th>Employee</th><th>Site</th><th>Role</th><th>Daily Rate</th></tr></thead>
                <tbody>
                    @forelse ($this->getLabourers() as $l)
                        <tr><td>{{ $l->employee?->full_name }}</td><td>{{ $l->site?->name }}</td><td><span class="badge gray">{{ $l->role_on_site }}</span></td><td>{{ $currency }} {{ number_format($l->daily_rate ?? 0, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No labourers assigned.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- MATERIALS --}}
        <div x-show="activeTab === 'materials'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Material Deliveries</div></div>
            <table>
                <thead><tr><th>Material</th><th>Site</th><th>Supplier</th><th>Quantity</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($this->getMaterialDeliveries() as $d)
                        <tr><td>{{ $d->material?->name }}</td><td>{{ $d->site?->name }}</td><td>{{ $d->supplier_name ?? '—' }}</td><td>{{ $d->quantity }}</td><td><span class="badge {{ $d->status === 'Delivered' ? 'success' : 'warning' }}">{{ $d->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="5" class="empty-row">No material deliveries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- SUBCONTRACTORS --}}
        <div x-show="activeTab === 'subcontractors'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Subcontractors</div></div>
            <table>
                <thead><tr><th>Company</th><th>Trade</th><th>Contract Value</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($this->getSubcontractors() as $s)
                        <tr><td>{{ $s->company_name }}</td><td>{{ $s->trade_specialty ?? '—' }}</td><td>{{ $currency }} {{ number_format($s->contract_value ?? 0, 2) }}</td><td><span class="badge {{ $s->status === 'Active' ? 'success' : 'gray' }}">{{ $s->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No subcontractors.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FINANCE --}}
        <div x-show="activeTab === 'finance'">
            @php $fin = $this->getFinanceSummary(); @endphp
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div class="ov-card"><div class="ov-label">Budget</div><div class="ov-value">{{ $currency }} {{ number_format($fin['budget'], 0) }}</div></div>
                <div class="ov-card"><div class="ov-label">Spent</div><div class="ov-value">{{ $currency }} {{ number_format($fin['spent'], 0) }}</div></div>
                <div class="ov-card"><div class="ov-label">Revenue</div><div class="ov-value">{{ $currency }} {{ number_format($fin['revenue'], 0) }}</div></div>
                <div class="ov-card"><div class="ov-label">Outstanding</div><div class="ov-value">{{ $currency }} {{ number_format($fin['outstanding'], 0) }}</div></div>
                <div class="ov-card"><div class="ov-label">Profit</div><div class="ov-value">{{ $currency }} {{ number_format($fin['profit'], 0) }}</div></div>
                <div class="ov-card"><div class="ov-label">Budget Used</div><div class="ov-value">{{ $fin['budget_used_percent'] }}%</div></div>
            </div>
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

        {{-- SAFETY --}}
        <div x-show="activeTab === 'safety'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Safety Incidents</div></div>
            <table>
                <thead><tr><th>Site</th><th>Date</th><th>Severity</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($this->getSafetyIncidents() as $i)
                        <tr><td>{{ $i->site?->name }}</td><td>{{ $i->date?->format('d M Y') }}</td><td><span class="badge {{ in_array($i->severity, ['High','Critical']) ? 'danger' : 'warning' }}">{{ $i->severity }}</span></td><td>{{ $i->status }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No safety incidents reported.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- QUALITY --}}
        <div x-show="activeTab === 'quality'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Quality Inspections</div></div>
            <table>
                <thead><tr><th>Site</th><th>Item</th><th>Date</th><th>Result</th></tr></thead>
                <tbody>
                    @forelse ($this->getQualityInspections() as $q)
                        <tr><td>{{ $q->site?->name }}</td><td>{{ $q->checklist_item ?? '—' }}</td><td>{{ $q->date?->format('d M Y') }}</td><td><span class="badge {{ $q->result === 'Pass' ? 'success' : 'danger' }}">{{ $q->result }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No quality inspections.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- DAILY DIARY --}}
        <div x-show="activeTab === 'diary'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Daily Site Diary</div></div>
            <table>
                <thead><tr><th>Site</th><th>Date</th><th>Weather</th><th>Workers</th></tr></thead>
                <tbody>
                    @forelse ($this->getDailyDiaries() as $d)
                        <tr><td>{{ $d->site?->name }}</td><td>{{ $d->date?->format('d M Y') }}</td><td>{{ $d->weather ?? '—' }}</td><td>{{ $d->workers_on_site }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No daily diary entries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- TIMELINE --}}
        <div x-show="activeTab === 'timeline'">
            <div class="mb-3 flex justify-end">{{ $this->addMilestoneAction() }}</div>
            <div class="space-y-3">
                @forelse ($this->getMilestones() as $milestone)
                    <div class="ov-card flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-950 dark:text-white">{{ $milestone->name }}</span>
                                @if ($milestone->is_critical_path)
                                    <x-filament::badge color="danger">Critical Path</x-filament::badge>
                                @endif
                            </div>
                            @if ($milestone->dependsOn)
                                <div class="text-xs text-gray-500 dark:text-gray-400">Depends on: {{ $milestone->dependsOn->name }}</div>
                            @endif
                        </div>
                        <div class="text-right text-sm">
                            <div class="text-gray-500 dark:text-gray-400">Planned: {{ $milestone->planned_date?->format('d M Y') ?? '—' }}</div>
                            <x-filament::badge :color="match($milestone->status) {
                                'Completed' => 'success', 'Delayed' => 'danger', 'In Progress' => 'warning', default => 'gray',
                            }">{{ $milestone->status }}</x-filament::badge>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No milestones yet. Use "Add Milestone" to create the project timeline.</p>
                @endforelse
            </div>
        </div>

        {{-- ANALYTICS --}}
        <div x-show="activeTab === 'analytics'" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @livewire(\App\Filament\Widgets\ProjectBudgetVsActualChart::class, ['record' => $project], key('pbva-'.$project->id))
            @livewire(\App\Filament\Widgets\ProjectProgressOverTimeChart::class, ['record' => $project], key('ppot-'.$project->id))
            @livewire(\App\Filament\Widgets\ProjectEquipmentUsageChart::class, ['record' => $project], key('peu-'.$project->id))
            @livewire(\App\Filament\Widgets\ProjectLabourProductivityChart::class, ['record' => $project], key('plp-'.$project->id))
            @livewire(\App\Filament\Widgets\ProjectMaterialConsumptionChart::class, ['record' => $project], key('pmc-'.$project->id))
            @livewire(\App\Filament\Widgets\ProjectSafetyIncidentsChart::class, ['record' => $project], key('psi-'.$project->id))
            @livewire(\App\Filament\Widgets\ProjectQualityIssuesChart::class, ['record' => $project], key('pqi-'.$project->id))
            @livewire(\App\Filament\Widgets\ProjectRevenueVsCostChart::class, ['record' => $project], key('prvc-'.$project->id))
        </div>

        {{-- SETTINGS --}}
        <div x-show="activeTab === 'settings'">
            <x-filament::section>
                <x-slot name="heading">Project Settings</x-slot>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                    <div><dt class="text-gray-500">Working Days</dt><dd>{{ $project->working_days ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Working Hours</dt><dd>{{ $project->working_hours ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Default Warehouse</dt><dd>{{ $project->default_warehouse ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Default Equipment Pool</dt><dd>{{ $project->default_equipment_pool ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Default Labour Pool</dt><dd>{{ $project->default_labour_pool ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Approval Workflow</dt><dd>{{ $project->approval_workflow ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Risk Level</dt><dd>{{ $project->risk_level ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Weather Monitoring</dt><dd>{{ $project->weather_monitoring_enabled ? 'Enabled' : 'Disabled' }}</dd></div>
                </dl>
                <div class="mt-4 flex items-center gap-2">
                    <button
                        type="button"
                        class="tm-pill"
                        style="cursor:pointer;"
                        x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                    >
                        <span x-show="!copied">🔗 Share (copy link)</span>
                        <span x-show="copied" x-cloak>✓ Link copied!</span>
                    </button>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
