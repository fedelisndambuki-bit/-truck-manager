@php
    $stats = $this->getStats();
    $meetings = $this->getRecentMeetings();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'title' => 'Health & Safety',
        'subtitle' => 'Incident status, training/certification compliance, and safety meetings',
    ])

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Open Incidents</div>
            <div style="font-size:22px;font-weight:700;color:{{ $stats['open_incidents'] > 0 ? '#DC2626' : '#0F172A' }};margin-top:4px;">{{ $stats['open_incidents'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Critical Incidents</div>
            <div style="font-size:22px;font-weight:700;color:{{ $stats['critical_incidents'] > 0 ? '#DC2626' : '#0F172A' }};margin-top:4px;">{{ $stats['critical_incidents'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Training Compliance</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['training_compliance_percent'] }}%</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Certification Compliance</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['certification_compliance_percent'] }}%</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Certifications Expiring (30d)</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['expiring_certifications'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">PPE Due for Replacement</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['ppe_due_for_replacement'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Safety Meetings This Month</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['meetings_this_month'] }}</div>
        </div>
    </div>

    <x-filament::section>
        <x-slot name="heading">Recent Safety Meetings / Toolbox Talks</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ \App\Filament\Resources\SafetyMeetingResource::getUrl('create') }}" class="fi-btn fi-color-primary fi-btn-size-sm text-xs font-semibold text-primary-600 hover:underline">
                + Log Meeting
            </a>
        </x-slot>
        <table class="fi-ta-table w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th class="py-2">Title</th><th>Site</th><th>Date</th><th>Attendees</th></tr></thead>
            <tbody>
                @forelse ($meetings as $meeting)
                    <tr class="border-t border-gray-100 dark:border-white/10">
                        <td class="py-2">
                            <a href="{{ \App\Filament\Resources\SafetyMeetingResource::getUrl('edit', ['record' => $meeting->id]) }}" class="text-primary-600 hover:underline">{{ $meeting->title }}</a>
                        </td>
                        <td>{{ $meeting->site?->name ?? '—' }}</td>
                        <td>{{ $meeting->date->format('d M Y') }}</td>
                        <td>{{ $meeting->attendees_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-gray-400">No safety meetings logged yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
