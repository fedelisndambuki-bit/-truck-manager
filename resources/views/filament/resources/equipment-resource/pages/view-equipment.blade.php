@php
    use App\Filament\Resources\EquipmentResource;
    $equipment = $record;
    $currency = session('currency', 'KES');
    $currentAllocation = $equipment->currentAllocation();
    $currentSite = $this->getCurrentSite();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63" />',
        'title' => $equipment->name,
        'subtitle' => $equipment->equipment_code . ' — ' . $equipment->equipment_type,
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
                'overview' => 'Overview', 'history' => 'Allocation History', 'fuel' => 'Fuel', 'maintenance' => 'Maintenance',
                'tyres' => 'Tyres', 'operators' => 'Operators', 'inspections' => 'Inspections', 'utilization' => 'Utilization',
                'gps' => 'GPS', 'documents' => 'Documents', 'reports' => 'Reports',
            ] as $key => $label)
                <div class="tab" :class="{ 'active': activeTab === '{{ $key }}' }" @click="activeTab = '{{ $key }}'">{{ $label }}</div>
            @endforeach
        </div>

        {{-- OVERVIEW --}}
        <div x-show="activeTab === 'overview'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div class="ov-card">
                    <div class="ov-label">Status</div>
                    <div class="mt-1"><x-filament::badge :color="\App\Filament\Resources\EquipmentResource::getStatusColor($equipment->status)">{{ $equipment->status }}</x-filament::badge></div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Current Project</div>
                    <div class="ov-value" style="font-size:14px;">{{ $currentAllocation?->project?->name ?? '—' }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Current Site</div>
                    <div class="ov-value" style="font-size:14px;">{{ $currentSite?->name ?? '—' }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Operator</div>
                    <div class="ov-value" style="font-size:14px;">{{ $currentAllocation?->operator?->full_name ?? 'Unassigned' }}</div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card"><div class="ov-label">Engine Hours</div><div class="ov-value">{{ number_format($equipment->engine_hours ?? 0, 0) }}</div></div>
                <div class="ov-card"><div class="ov-label">Idle Hours</div><div class="ov-value">{{ number_format($equipment->idle_hours ?? 0, 0) }}</div></div>
                <div class="ov-card"><div class="ov-label">Fuel Level</div><div class="ov-value">{{ $equipment->fuel_level ?? '—' }}%</div></div>
                <div class="ov-card"><div class="ov-label">Current Value</div><div class="ov-value">{{ $currency }} {{ number_format($equipment->current_value ?? 0, 0) }}</div></div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Warranty & Insurance</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Insurance Provider</dt><dd>{{ $equipment->insurance_provider ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Insurance Expiry</dt><dd>{{ $equipment->insurance_expiry?->format('d M Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Warranty Expiry</dt><dd>{{ $equipment->warranty_expiry?->format('d M Y') ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
                <x-filament::section>
                    <x-slot name="heading">Upcoming Maintenance</x-slot>
                    @php $upcoming = $equipment->maintenances()->whereNotNull('next_maintenance_date')->where('next_maintenance_date', '>=', now())->orderBy('next_maintenance_date')->first(); @endphp
                    @if ($upcoming)
                        <p class="text-sm">{{ $upcoming->maintenance_type }} due {{ $upcoming->next_maintenance_date->format('d M Y') }}</p>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming maintenance scheduled.</p>
                    @endif
                </x-filament::section>
            </div>
        </div>

        {{-- ALLOCATION HISTORY --}}
        <div x-show="activeTab === 'history'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Allocation History</div></div>
            <table>
                <thead><tr><th>Project</th><th>Site</th><th>Operator</th><th>From</th><th>To</th><th>Hours Worked</th><th>Fuel Used</th></tr></thead>
                <tbody>
                    @forelse ($this->getAllocationHistory() as $a)
                        <tr>
                            <td>{{ $a->project?->name ?? '—' }}</td>
                            <td>{{ $a->site?->name ?? '—' }}</td>
                            <td>{{ $a->operator?->full_name ?? '—' }}</td>
                            <td>{{ $a->allocated_from?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $a->actual_return_date?->format('d M Y') ?? $a->allocated_to?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $a->operating_hours_today }}</td>
                            <td>{{ $a->fuel_consumed_today }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-row">No allocation history.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FUEL --}}
        <div x-show="activeTab === 'fuel'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Fuel (Fleet Management)</div></div>
            <table>
                <thead><tr><th>Date</th><th>Station</th><th>Quantity (L)</th><th>Cost/L</th><th>Total Cost</th></tr></thead>
                <tbody>
                    @forelse ($this->getFuelLogs() as $f)
                        <tr>
                            <td>{{ $f->date?->format('d M Y') }}</td>
                            <td>{{ $f->fuel_station ?? '—' }}</td>
                            <td>{{ $f->quantity_litres }}</td>
                            <td>{{ $currency }} {{ number_format($f->cost_per_litre, 2) }}</td>
                            <td>{{ $currency }} {{ number_format($f->total_cost, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-row">No fuel logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @php $logs = $this->getFuelLogs(); @endphp
            @if ($logs->isNotEmpty())
                <div class="p-4 text-xs text-gray-500 dark:text-gray-400">
                    Average consumption: {{ number_format($logs->avg('quantity_litres'), 1) }} L/fill · Total cost: {{ $currency }} {{ number_format($logs->sum('total_cost'), 2) }}
                </div>
            @endif
        </div>

        {{-- MAINTENANCE --}}
        <div x-show="activeTab === 'maintenance'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Maintenance (Fleet Management)</div></div>
            <table>
                <thead><tr><th>Date</th><th>Type</th><th>Provider</th><th>Cost</th><th>Status</th><th>Next Due</th></tr></thead>
                <tbody>
                    @forelse ($this->getMaintenanceRecords() as $m)
                        <tr>
                            <td>{{ $m->maintenance_date?->format('d M Y') }}</td>
                            <td><span class="badge gray">{{ $m->maintenance_type }}</span></td>
                            <td>{{ $m->service_provider ?? '—' }}</td>
                            <td>{{ $currency }} {{ number_format($m->cost ?? 0, 2) }}</td>
                            <td><span class="badge {{ $m->status === 'Completed' ? 'success' : 'warning' }}">{{ $m->status }}</span></td>
                            <td>{{ $m->next_maintenance_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">No maintenance records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- TYRES --}}
        <div x-show="activeTab === 'tyres'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Tyres (Tyre Management)</div></div>
            <table>
                <thead><tr><th>Position</th><th>Brand</th><th>Tread Depth</th><th>Pressure</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($this->getTyres() as $t)
                        <tr>
                            <td>{{ $t->position ?? '—' }}</td>
                            <td>{{ $t->brand }}</td>
                            <td>{{ $t->tread_depth }} mm</td>
                            <td>{{ $t->pressure ?? '—' }}</td>
                            <td><span class="badge {{ $t->status === 'Active' ? 'success' : 'gray' }}">{{ $t->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-row">No tyres attached yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- OPERATORS --}}
        <div x-show="activeTab === 'operators'" class="table-section">
            <div class="table-header-custom"><div class="table-title">Operators</div></div>
            <table>
                <thead><tr><th>Operator</th><th>Project</th><th>From</th><th>To</th></tr></thead>
                <tbody>
                    @forelse ($this->getOperators() as $o)
                        <tr>
                            <td>{{ $o->operator?->full_name }}</td>
                            <td>{{ $o->project?->name ?? '—' }}</td>
                            <td>{{ $o->allocated_from?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $o->actual_return_date?->format('d M Y') ?? $o->allocated_to?->format('d M Y') ?? 'Current' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">No operators logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- INSPECTIONS --}}
        <div x-show="activeTab === 'inspections'">
            <div class="mb-3 flex justify-end">{{ $this->addInspectionAction() }}</div>
            <div class="table-section">
                <div class="table-header-custom"><div class="table-title">Inspections</div></div>
                <table>
                    <thead><tr><th>Date</th><th>Type</th><th>Inspector</th><th>Defects</th><th>Approved</th></tr></thead>
                    <tbody>
                        @forelse ($this->getInspections() as $i)
                            <tr>
                                <td>{{ $i->date?->format('d M Y') }}</td>
                                <td><span class="badge gray">{{ $i->type }}</span></td>
                                <td>{{ $i->inspector?->full_name ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($i->defects, 40) ?: 'None' }}</td>
                                <td><span class="badge {{ $i->supervisor_approved ? 'success' : 'warning' }}">{{ $i->supervisor_approved ? 'Yes' : 'Pending' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-row">No inspections logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- UTILIZATION --}}
        <div x-show="activeTab === 'utilization'" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @livewire(\App\Filament\Widgets\EquipmentOperatingHoursChart::class, ['record' => $equipment], key('eoh-'.$equipment->id))
            @livewire(\App\Filament\Widgets\EquipmentUtilizationRateChart::class, ['record' => $equipment], key('eur-'.$equipment->id))
            @livewire(\App\Filament\Widgets\EquipmentDowntimeChart::class, ['record' => $equipment], key('edt-'.$equipment->id))
        </div>

        {{-- GPS --}}
        <div x-show="activeTab === 'gps'">
            <x-filament::section>
                <x-slot name="heading">Current Location</x-slot>
                @if ($currentSite && $currentSite->gps_lat && $currentSite->gps_lng)
                    <p class="text-sm text-gray-700 dark:text-gray-300">Currently at <strong>{{ $currentSite->name }}</strong> ({{ $currentSite->gps_lat }}, {{ $currentSite->gps_lng }}).</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This equipment has no independent GPS hardware — location reflects its currently assigned site, not a live tracking feed.</p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No current site location available.</p>
                @endif
            </x-filament::section>
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

        {{-- REPORTS --}}
        <div x-show="activeTab === 'reports'">
            <p class="text-sm text-gray-500 dark:text-gray-400">Use "Generate Report" in the header above to download this equipment's PDF summary (allocation history, fuel, maintenance).</p>
        </div>
    </div>
</x-filament-panels::page>
