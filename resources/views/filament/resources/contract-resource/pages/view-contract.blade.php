@php
    use App\Filament\Resources\ContractResource;
    $contract = $record;
    $currency = session('currency', 'KES');
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'title' => $contract->contract_number,
        'subtitle' => $contract->subcontractor->company_name . ' — ' . $contract->project->name,
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
                'overview' => 'Overview', 'scope' => 'Scope of Work', 'assignments' => 'Assignments',
                'workorders' => 'Work Orders', 'progress' => 'Progress', 'variations' => 'Variations',
                'certificates' => 'Payment Certificates',
            ] as $key => $label)
                <div class="tab" :class="{ 'active': activeTab === '{{ $key }}' }" @click="activeTab = '{{ $key }}'">{{ $label }}</div>
            @endforeach
        </div>

        {{-- OVERVIEW --}}
        <div x-show="activeTab === 'overview'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="ov-card">
                    <div class="ov-label">Status</div>
                    <div class="mt-1"><x-filament::badge :color="ContractResource::getStatusColor($contract->status)">{{ $contract->status }}</x-filament::badge></div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Contract Value</div>
                    <div class="ov-value">{{ $currency }} {{ number_format($contract->contract_value, 0) }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Revised Value</div>
                    <div class="ov-value">{{ $currency }} {{ number_format($contract->revised_contract_value, 0) }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Retention</div>
                    <div class="ov-value">{{ $contract->retention_percent }}%</div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Scope Summary</x-slot>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $contract->scope_summary ?? 'No scope summary recorded.' }}</p>
                </x-filament::section>
                <x-filament::section>
                    <x-slot name="heading">Dates & Terms</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Start Date</dt><dd>{{ $contract->start_date?->format('d M Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Completion Date</dt><dd>{{ $contract->completion_date?->format('d M Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Payment Terms</dt><dd>{{ $contract->payment_terms ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Performance Bond</dt><dd>{{ $contract->performance_bond_amount ? $currency . ' ' . number_format($contract->performance_bond_amount, 2) : '—' }}</dd></div>
                    </dl>
                </x-filament::section>
            </div>
        </div>

        {{-- SCOPE OF WORK --}}
        <div x-show="activeTab === 'scope'">
            @forelse ($this->getScopeOfWorks() as $scope)
                <x-filament::section class="mb-4">
                    <x-slot name="heading">Scope — Expected Completion: {{ $scope->expected_completion?->format('d M Y') ?? '—' }} ({{ $scope->percent_complete }}% complete)</x-slot>
                    <table class="fi-ta-table w-full text-sm">
                        <thead><tr class="text-left text-gray-500"><th class="py-2">Deliverable</th><th>Activity</th><th>BOQ Ref</th><th>Qty</th><th>Unit Rate</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($scope->items as $item)
                                <tr class="border-t border-gray-100 dark:border-white/10">
                                    <td class="py-2">{{ $item->deliverable }}</td>
                                    <td>{{ $item->activity ?? '—' }}</td>
                                    <td>{{ $item->boq_reference ?? '—' }}</td>
                                    <td>{{ number_format($item->quantity, 2) }} {{ $item->unit }}</td>
                                    <td>{{ number_format($item->unit_rate, 2) }}</td>
                                    <td>{{ number_format($item->amount, 2) }}</td>
                                    <td>{{ $item->status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-filament::section>
            @empty
                <x-filament::section>
                    <x-slot name="heading">Scope of Work</x-slot>
                    <p class="text-sm text-gray-400">No scope of work defined for this contract yet.</p>
                </x-filament::section>
            @endforelse
        </div>

        {{-- ASSIGNMENTS --}}
        <div x-show="activeTab === 'assignments'">
            <x-filament::section>
                <x-slot name="heading">Project Assignments</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Site</th><th>Phase</th><th>Work Area</th><th>Site Manager</th><th>Supervisor</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getAssignments() as $a)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $a->site?->name ?? '—' }}</td>
                                <td>{{ $a->construction_phase ?? '—' }}</td>
                                <td>{{ $a->work_area ?? '—' }}</td>
                                <td>{{ $a->siteManager?->full_name ?? '—' }}</td>
                                <td>{{ $a->supervisor?->full_name ?? '—' }}</td>
                                <td>{{ $a->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-4 text-center text-gray-400">No assignments recorded for this contract yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- WORK ORDERS --}}
        <div x-show="activeTab === 'workorders'">
            <x-filament::section>
                <x-slot name="heading">Work Orders</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">WO #</th><th>Site</th><th>Priority</th><th>Status</th><th>Expected Completion</th><th>Supervisor</th></tr></thead>
                    <tbody>
                        @forelse ($this->getWorkOrders() as $wo)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $wo->work_order_number }}</td>
                                <td>{{ $wo->site?->name ?? '—' }}</td>
                                <td>{{ $wo->priority }}</td>
                                <td>{{ $wo->status }}</td>
                                <td>{{ $wo->expected_completion?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $wo->supervisor?->full_name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-4 text-center text-gray-400">No work orders issued under this contract yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- PROGRESS --}}
        <div x-show="activeTab === 'progress'">
            <x-filament::section>
                <x-slot name="heading">Progress Updates</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Date</th><th>Period</th><th>% Complete</th><th>Milestone</th><th>Approved</th></tr></thead>
                    <tbody>
                        @forelse ($this->getProgressUpdates() as $update)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $update->date->format('d M Y') }}</td>
                                <td>{{ $update->period_type }}</td>
                                <td>{{ $update->percent_complete }}%</td>
                                <td>{{ $update->milestone?->name ?? '—' }}</td>
                                <td>{{ $update->supervisor_approved ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No progress updates recorded yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- VARIATIONS --}}
        <div x-show="activeTab === 'variations'">
            <x-filament::section>
                <x-slot name="heading">Variations</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Variation #</th><th>Cost Impact</th><th>Time Extension</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getVariations() as $var)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $var->variation_number }}</td>
                                <td>{{ $currency }} {{ number_format($var->cost_impact, 2) }}</td>
                                <td>{{ $var->time_extension_days }} days</td>
                                <td>{{ $var->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No variations recorded for this contract</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- PAYMENT CERTIFICATES --}}
        <div x-show="activeTab === 'certificates'">
            <x-filament::section>
                <x-slot name="heading">Payment Certificates</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Certificate #</th><th>Type</th><th>Gross</th><th>Net Due</th><th>Outstanding</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getPaymentCertificates() as $cert)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $cert->certificate_number }}</td>
                                <td>{{ $cert->certificate_type }}</td>
                                <td>{{ $currency }} {{ number_format($cert->gross_amount, 2) }}</td>
                                <td>{{ $currency }} {{ number_format($cert->net_amount_due, 2) }}</td>
                                <td>{{ $currency }} {{ number_format($cert->outstanding_balance, 2) }}</td>
                                <td>{{ $cert->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-4 text-center text-gray-400">No payment certificates issued for this contract yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
