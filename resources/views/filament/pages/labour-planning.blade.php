@php
    $stats = $this->getStats();
    $currency = session('currency', 'KES');
    $departments = $this->getDepartmentBreakdown();
    $vacancies = $this->getVacancySummary();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
        'title' => 'Labour Planning',
        'subtitle' => 'Headcount, cost projection, and vacancy gaps across departments',
    ])

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Total Staff</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['total_staff'] }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Monthly Labour Cost</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $currency }} {{ number_format($stats['monthly_labour_cost'], 2) }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Open Vacancies</div>
            <div style="font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $stats['open_vacancies'] }}</div>
        </div>
    </div>

    <x-filament::section>
        <x-slot name="heading">Headcount & Cost by Department</x-slot>
        <table class="fi-ta-table w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500">
                    <th class="py-2">Department</th>
                    <th>Staff</th>
                    <th>Positions</th>
                    <th>Open Vacancies</th>
                    <th>Monthly Labour Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $dept)
                    <tr class="border-t border-gray-100 dark:border-white/10">
                        <td class="py-2">{{ $dept['name'] }}</td>
                        <td>{{ $dept['staff_count'] }}</td>
                        <td>{{ $dept['positions_count'] }}</td>
                        <td>{{ $vacancies[$dept['name']] ?? 0 }}</td>
                        <td>{{ $currency }} {{ number_format($dept['monthly_labour_cost'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-gray-400">No departments configured yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
