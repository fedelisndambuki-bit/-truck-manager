@php
    use App\Filament\Resources\SubcontractorResource;
    $sub = $record;
    $currency = session('currency', 'KES');
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 .621-.504 1.125-1.125 1.125h-15c-.621 0-1.125-.504-1.125-1.125v-4.25m17.25 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />',
        'title' => $sub->company_name,
        'subtitle' => $sub->subcontractor_code . ' — ' . ($sub->specialization?->name ?? 'No specialization'),
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
                'overview' => 'Overview', 'prequalification' => 'Prequalification', 'contracts' => 'Contracts',
                'assignments' => 'Assignments', 'workorders' => 'Work Orders', 'variations' => 'Variations',
                'certificates' => 'Payment Certificates', 'performance' => 'Performance', 'documents' => 'Documents',
            ] as $key => $label)
                <div class="tab" :class="{ 'active': activeTab === '{{ $key }}' }" @click="activeTab = '{{ $key }}'">{{ $label }}</div>
            @endforeach
        </div>

        {{-- OVERVIEW --}}
        <div x-show="activeTab === 'overview'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div class="ov-card">
                    <div class="ov-label">Status</div>
                    <div class="mt-1"><x-filament::badge :color="SubcontractorResource::getStatusColor($sub->status)">{{ $sub->status }}</x-filament::badge></div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Compliance</div>
                    <div class="mt-1"><x-filament::badge :color="$sub->compliance_status === 'Compliant' ? 'success' : 'danger'">{{ $sub->compliance_status }}</x-filament::badge></div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Avg. Performance</div>
                    <div class="ov-value">{{ $sub->average_performance_rating ? round($sub->average_performance_rating, 1) . ' / 5' : '—' }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Category</div>
                    <div class="ov-value">{{ $sub->category ?? '—' }}</div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Contact Details</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Contact Person</dt><dd>{{ $sub->contact_person ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd>{{ $sub->phone ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>{{ $sub->email ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Address</dt><dd>{{ $sub->office_address ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
                <x-filament::section>
                    <x-slot name="heading">Registration & Compliance</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Registration No.</dt><dd>{{ $sub->registration_number ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Tax PIN</dt><dd>{{ $sub->tax_pin ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Insurance Expiry</dt><dd>{{ $sub->insurance_expiry_date?->format('d M Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">License Expiry</dt><dd>{{ $sub->business_license_expiry_date?->format('d M Y') ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
            </div>
        </div>

        {{-- PREQUALIFICATION --}}
        <div x-show="activeTab === 'prequalification'">
            <x-filament::section>
                <x-slot name="heading">Prequalification History</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Date</th><th>Score</th><th>Insurance Verified</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getPrequalifications() as $prequal)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $prequal->created_at->format('d M Y') }}</td>
                                <td>{{ $prequal->score ?? '—' }} / 100</td>
                                <td>{{ $prequal->insurance_verified ? 'Yes' : 'No' }}</td>
                                <td>{{ $prequal->approval_status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No prequalification assessments yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- CONTRACTS --}}
        <div x-show="activeTab === 'contracts'">
            <x-filament::section>
                <x-slot name="heading">Contracts</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Contract #</th><th>Project</th><th>Value</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getContracts() as $c)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">
                                    <a href="{{ \App\Filament\Resources\ContractResource::getUrl('view', ['record' => $c->id]) }}" class="text-primary-600 hover:underline">{{ $c->contract_number }}</a>
                                </td>
                                <td>{{ $c->project?->name ?? '—' }}</td>
                                <td>{{ $currency }} {{ number_format($c->contract_value, 2) }}</td>
                                <td>{{ $c->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No contracts awarded yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- ASSIGNMENTS --}}
        <div x-show="activeTab === 'assignments'">
            <x-filament::section>
                <x-slot name="heading">Project Assignments</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Project</th><th>Site</th><th>Phase</th><th>Site Manager</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getAssignments() as $a)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $a->project?->name ?? '—' }}</td>
                                <td>{{ $a->site?->name ?? '—' }}</td>
                                <td>{{ $a->construction_phase ?? '—' }}</td>
                                <td>{{ $a->siteManager?->full_name ?? '—' }}</td>
                                <td>{{ $a->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No project assignments yet</td></tr>
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
                    <thead><tr class="text-left text-gray-500"><th class="py-2">WO #</th><th>Project</th><th>Priority</th><th>Status</th><th>Expected Completion</th><th>Supervisor</th></tr></thead>
                    <tbody>
                        @forelse ($this->getWorkOrders() as $wo)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $wo->work_order_number }}</td>
                                <td>{{ $wo->project?->name ?? '—' }}</td>
                                <td>{{ $wo->priority }}</td>
                                <td>{{ $wo->status }}</td>
                                <td>{{ $wo->expected_completion?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $wo->supervisor?->full_name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-4 text-center text-gray-400">No work orders issued yet</td></tr>
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
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Variation #</th><th>Contract</th><th>Cost Impact</th><th>Time Extension</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getVariations() as $var)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $var->variation_number }}</td>
                                <td>{{ $var->contract?->contract_number ?? '—' }}</td>
                                <td>{{ $currency }} {{ number_format($var->cost_impact, 2) }}</td>
                                <td>{{ $var->time_extension_days }} days</td>
                                <td>{{ $var->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No variations recorded</td></tr>
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
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Certificate #</th><th>Contract</th><th>Type</th><th>Net Due</th><th>Outstanding</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getPaymentCertificates() as $cert)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $cert->certificate_number }}</td>
                                <td>{{ $cert->contract?->contract_number ?? '—' }}</td>
                                <td>{{ $cert->certificate_type }}</td>
                                <td>{{ $currency }} {{ number_format($cert->net_amount_due, 2) }}</td>
                                <td>{{ $currency }} {{ number_format($cert->outstanding_balance, 2) }}</td>
                                <td>{{ $cert->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-4 text-center text-gray-400">No payment certificates issued</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- PERFORMANCE --}}
        <div x-show="activeTab === 'performance'">
            <x-filament::section>
                <x-slot name="heading">Performance Reviews</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Date</th><th>Quality</th><th>Timeliness</th><th>Safety</th><th>Overall</th></tr></thead>
                    <tbody>
                        @forelse ($this->getPerformanceReviews() as $review)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $review->review_date->format('d M Y') }}</td>
                                <td>{{ $review->quality_of_work_rating }} / 5</td>
                                <td>{{ $review->timeliness_rating }} / 5</td>
                                <td>{{ $review->safety_rating }} / 5</td>
                                <td class="font-semibold">{{ $review->overall_rating }} / 5</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No performance reviews yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- DOCUMENTS --}}
        <div x-show="activeTab === 'documents'">
            <x-filament::section>
                <x-slot name="heading">Documents</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Title</th><th>Category</th><th>Uploaded</th></tr></thead>
                    <tbody>
                        @forelse ($this->getDocuments() as $doc)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">
                                    <a href="{{ \App\Filament\Resources\DocumentResource::getUrl('edit', ['record' => $doc->id]) }}" class="text-primary-600 hover:underline">{{ $doc->title }}</a>
                                </td>
                                <td>{{ $doc->category }}</td>
                                <td>{{ $doc->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-center text-gray-400">No documents uploaded for this subcontractor</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
