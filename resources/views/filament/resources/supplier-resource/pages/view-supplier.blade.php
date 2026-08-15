@php
    $supplier = $record;
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.001" />',
        'title' => $supplier->company_name,
        'subtitle' => ($supplier->contact_person ?? 'No contact') . ' — ' . $supplier->status,
    ])

    <style>
        .tab-bar{display:flex;gap:2px;background:#F1F5F9;border-radius:10px;padding:4px;border:1px solid #E2E8F0;overflow-x:auto;margin-bottom:20px}
        .tab{padding:7px 14px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;color:#64748B;transition:all .15s ease;white-space:nowrap}
        .tab.active{background:#FFFFFF;color:#0F172A;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #E2E8F0}
        .ov-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px}
        .ov-label{font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
        .ov-value{font-size:20px;font-weight:700;color:#0F172A;margin-top:4px}
    </style>

    <div x-data="{ activeTab: 'profile' }">
        <div class="tab-bar">
            @foreach (['profile' => 'Profile', 'performance' => 'Performance', 'materials' => 'Preferred Materials', 'contract' => 'Contract'] as $key => $label)
                <div class="tab" :class="{ 'active': activeTab === '{{ $key }}' }" @click="activeTab = '{{ $key }}'">{{ $label }}</div>
            @endforeach
        </div>

        <div x-show="activeTab === 'profile'">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Contact Details</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Contact Person</dt><dd>{{ $supplier->contact_person ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd>{{ $supplier->phone ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>{{ $supplier->email ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Address</dt><dd>{{ $supplier->address ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
                <x-filament::section>
                    <x-slot name="heading">Tax & Bank</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Tax Number</dt><dd>{{ $supplier->tax_number ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Bank</dt><dd>{{ $supplier->bank_name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Account No.</dt><dd>{{ $supplier->bank_account_number ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
            </div>
        </div>

        <div x-show="activeTab === 'performance'">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="ov-card">
                    <div class="ov-label">Overall Performance</div>
                    <div class="ov-value">{{ $supplier->performance_rating ?? '—' }} / 5</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Delivery Performance</div>
                    <div class="ov-value">{{ $supplier->delivery_performance_rating ?? '—' }} / 5</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Quality Rating</div>
                    <div class="ov-value">{{ $supplier->quality_rating ?? '—' }} / 5</div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'materials'">
            <x-filament::section>
                <x-slot name="heading">Preferred Materials</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Code</th><th>Name</th><th>Category</th><th>Unit Cost</th></tr></thead>
                    <tbody>
                        @forelse ($this->getPreferredMaterials() as $material)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $material->material_code }}</td>
                                <td>{{ $material->name }}</td>
                                <td>{{ $material->category?->name ?? '—' }}</td>
                                <td>{{ number_format($material->unit_cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No materials set to prefer this supplier</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        <div x-show="activeTab === 'contract'">
            <x-filament::section>
                <x-slot name="heading">Contract</x-slot>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Start Date</dt><dd>{{ $supplier->contract_start_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">End Date</dt><dd>{{ $supplier->contract_end_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd>{{ $supplier->status }}</dd></div>
                </dl>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
