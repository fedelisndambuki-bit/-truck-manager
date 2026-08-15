@php
    $incidents = $this->getRecentIncidents();
    $hazards = $this->getTopHazards();
    $overdueActions = $this->getOverdueCorrectiveActions();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'title' => 'Safety & Incident Management',
        'subtitle' => 'Enterprise EHS overview — incidents, hazards, compliance and safety performance across all sites',
    ])

    {{-- Quick Actions --}}
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
        @php
            $quickActions = [
                ['label' => 'Report Incident', 'icon' => 'heroicon-o-exclamation-triangle', 'url' => \App\Filament\Resources\SafetyIncidentResource::getUrl('create')],
                ['label' => 'Report Hazard', 'icon' => 'heroicon-o-fire', 'url' => \App\Filament\Resources\HazardResource::getUrl('create')],
                ['label' => 'Create Risk Assessment', 'icon' => 'heroicon-o-clipboard-document-list', 'url' => \App\Filament\Resources\RiskAssessmentResource::getUrl('create')],
                ['label' => 'Schedule Inspection', 'icon' => 'heroicon-o-clipboard-document-check', 'url' => \App\Filament\Resources\SafetyInspectionResource::getUrl('create')],
                ['label' => 'Conduct Toolbox Talk', 'icon' => 'heroicon-o-megaphone', 'url' => \App\Filament\Resources\ToolboxTalkResource::getUrl('create')],
                ['label' => 'Assign Corrective Action', 'icon' => 'heroicon-o-wrench-screwdriver', 'url' => \App\Filament\Resources\CorrectiveActionResource::getUrl('create')],
                ['label' => 'Upload Document', 'icon' => 'heroicon-o-document-plus', 'url' => \App\Filament\Resources\SafetyDocumentResource::getUrl('create')],
                ['label' => 'Reports', 'icon' => 'heroicon-o-chart-bar', 'url' => \App\Filament\Pages\SafetyReports::getUrl()],
            ];
        @endphp
        @foreach ($quickActions as $action)
            <a href="{{ $action['url'] }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;font-size:12.5px;font-weight:600;color:#2f4f4f;text-decoration:none;transition:all .15s;">
                <x-filament::icon :icon="$action['icon']" style="width:15px;height:15px;" />
                {{ $action['label'] }}
            </a>
        @endforeach
    </div>

    {{-- KPI Cards --}}
    @livewire(\App\Filament\Widgets\SafetyKpiWidget::class)

    {{-- Charts --}}
    <div class="fi-section-content-ctn" style="margin-top:20px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px;">
            @livewire(\App\Filament\Widgets\SafetyMonthlyIncidentsChart::class)
            @livewire(\App\Filament\Widgets\SafetyIncidentTypesChart::class)
            @livewire(\App\Filament\Widgets\SafetyRiskLevelsChart::class)
            @livewire(\App\Filament\Widgets\SafetyHazardsBySiteChart::class)
            @livewire(\App\Filament\Widgets\SafetyPpeComplianceChart::class)
            @livewire(\App\Filament\Widgets\SafetyInspectionTrendsChart::class)
            @livewire(\App\Filament\Widgets\SafetyCorrectiveActionStatusChart::class)
            @livewire(\App\Filament\Widgets\SafetyTrainingComplianceChart::class)
        </div>
    </div>

    {{-- Recent activity panels --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:16px;margin-top:20px;">
        <x-filament::section>
            <x-slot name="heading">Recent Incidents</x-slot>
            <x-slot name="headerEnd">
                <a href="{{ \App\Filament\Resources\SafetyIncidentResource::getUrl('index') }}" class="text-xs font-semibold text-primary-600 hover:underline">View all</a>
            </x-slot>
            <table class="fi-ta-table w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th class="py-2">#</th><th>Site</th><th>Severity</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($incidents as $incident)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-2"><a href="{{ \App\Filament\Resources\SafetyIncidentResource::getUrl('edit', ['record' => $incident->id]) }}" class="text-primary-600 hover:underline">{{ $incident->incident_number }}</a></td>
                            <td>{{ $incident->site?->name ?? '—' }}</td>
                            <td><span class="badge {{ match($incident->severity) { 'Low' => 'success', 'Medium' => 'warning', default => 'danger' } }}">{{ $incident->severity }}</span></td>
                            <td>{{ $incident->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-gray-400">No incidents logged</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Top Open Hazards</x-slot>
            <x-slot name="headerEnd">
                <a href="{{ \App\Filament\Resources\HazardResource::getUrl('index') }}" class="text-xs font-semibold text-primary-600 hover:underline">View all</a>
            </x-slot>
            <table class="fi-ta-table w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th class="py-2">Hazard</th><th>Site</th><th>Risk</th></tr></thead>
                <tbody>
                    @forelse ($hazards as $hazard)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-2"><a href="{{ \App\Filament\Resources\HazardResource::getUrl('edit', ['record' => $hazard->id]) }}" class="text-primary-600 hover:underline">{{ $hazard->hazard_type }}</a></td>
                            <td>{{ $hazard->site?->name ?? '—' }}</td>
                            <td><span class="badge danger">{{ $hazard->risk_rating }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-400">No high-risk hazards open</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Overdue Corrective Actions</x-slot>
            <x-slot name="headerEnd">
                <a href="{{ \App\Filament\Resources\CorrectiveActionResource::getUrl('index') }}" class="text-xs font-semibold text-primary-600 hover:underline">View all</a>
            </x-slot>
            <table class="fi-ta-table w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th class="py-2">Action</th><th>Responsible</th><th>Due</th></tr></thead>
                <tbody>
                    @forelse ($overdueActions as $action)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-2"><a href="{{ \App\Filament\Resources\CorrectiveActionResource::getUrl('edit', ['record' => $action->id]) }}" class="text-primary-600 hover:underline">{{ $action->action_number }}</a></td>
                            <td>{{ $action->responsibleStaff?->full_name ?? '—' }}</td>
                            <td class="text-danger-600 font-semibold">{{ $action->due_date?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-400">Nothing overdue</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
