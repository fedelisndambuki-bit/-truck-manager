@php
    $stats = $this->getStats();
    $nonCompliant = $this->getNonCompliantSubcontractors();
    $expiringContracts = $this->getExpiringContracts();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'title' => 'Subcontractor Compliance',
        'subtitle' => 'Insurance, licensing, NCA registration, and contract expiry monitoring',
    ])

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Compliant</div>
            <div style="font-size:22px;font-weight:700;color:#16A34A;margin-top:4px;">{{ $stats['compliant'] }} / {{ $stats['total_subcontractors'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Non-Compliant</div>
            <div style="font-size:22px;font-weight:700;color:{{ $stats['non_compliant'] > 0 ? '#DC2626' : '#0F172A' }};margin-top:4px;">{{ $stats['non_compliant'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Insurance Expired</div>
            <div style="font-size:22px;font-weight:700;color:{{ $stats['insurance_expired'] > 0 ? '#DC2626' : '#0F172A' }};margin-top:4px;">{{ $stats['insurance_expired'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Insurance Expiring (30d)</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['insurance_expiring'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">License Expired</div>
            <div style="font-size:22px;font-weight:700;color:{{ $stats['license_expired'] > 0 ? '#DC2626' : '#0F172A' }};margin-top:4px;">{{ $stats['license_expired'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">License Expiring (30d)</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['license_expiring'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">NCA Expired / Expiring</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['nca_expired'] }} / {{ $stats['nca_expiring'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Contracts Expiring (30d)</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['expiring_contracts'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Non-Compliant Subcontractors</x-slot>
            <table class="fi-ta-table w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th class="py-2">Subcontractor</th><th>Issue(s)</th></tr></thead>
                <tbody>
                    @forelse ($nonCompliant as $sub)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-2">
                                <a href="{{ \App\Filament\Resources\SubcontractorResource::getUrl('view', ['record' => $sub->id]) }}" class="text-primary-600 hover:underline">{{ $sub->company_name }}</a>
                            </td>
                            <td class="text-red-600">{{ $sub->compliance_status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-6 text-center text-gray-400">All subcontractors are compliant</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Contracts Expiring in 30 Days</x-slot>
            <table class="fi-ta-table w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th class="py-2">Contract #</th><th>Subcontractor</th><th>Completion Date</th></tr></thead>
                <tbody>
                    @forelse ($expiringContracts as $c)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-2">
                                <a href="{{ \App\Filament\Resources\ContractResource::getUrl('view', ['record' => $c->id]) }}" class="text-primary-600 hover:underline">{{ $c->contract_number }}</a>
                            </td>
                            <td>{{ $c->subcontractor?->company_name ?? '—' }}</td>
                            <td>{{ $c->completion_date?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-400">No contracts expiring soon</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
