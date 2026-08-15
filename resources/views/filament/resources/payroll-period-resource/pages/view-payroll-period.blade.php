@php
    use App\Filament\Resources\PayrollPeriodResource;
    $period = $record;
    $currency = session('currency', 'KES');
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />',
        'title' => PayrollPeriodResource::getMonthOptions()[$period->month] . ' ' . $period->year,
        'subtitle' => 'Payroll run — ' . $period->status,
    ])

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div class="ov-card" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Status</div>
            <div class="mt-1"><x-filament::badge :color="PayrollPeriodResource::getStatusColor($period->status)">{{ $period->status }}</x-filament::badge></div>
        </div>
        <div class="ov-card" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Total Gross</div>
            <div style="font-size:20px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $currency }} {{ number_format($period->total_gross, 2) }}</div>
        </div>
        <div class="ov-card" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Total Deductions</div>
            <div style="font-size:20px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $currency }} {{ number_format($period->total_deductions, 2) }}</div>
        </div>
        <div class="ov-card" style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Total Net</div>
            <div style="font-size:20px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $currency }} {{ number_format($period->total_net, 2) }}</div>
        </div>
    </div>

    <x-filament::section>
        <x-slot name="heading">Payslips</x-slot>
        <table class="fi-ta-table w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500">
                    <th class="py-2">Employee</th>
                    <th>Gross</th>
                    <th>NSSF</th>
                    <th>SHA</th>
                    <th>Housing Levy</th>
                    <th>PAYE</th>
                    <th>Loan</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->getPayslips() as $payslip)
                    <tr class="border-t border-gray-100 dark:border-white/10">
                        <td class="py-2">{{ $payslip->staff->full_name }}</td>
                        <td>{{ number_format($payslip->gross_pay, 2) }}</td>
                        <td>{{ number_format($payslip->nssf_amount, 2) }}</td>
                        <td>{{ number_format($payslip->sha_amount, 2) }}</td>
                        <td>{{ number_format($payslip->housing_levy_amount, 2) }}</td>
                        <td>{{ number_format($payslip->paye_amount, 2) }}</td>
                        <td>{{ number_format($payslip->loan_deduction, 2) }}</td>
                        <td class="font-semibold">{{ number_format($payslip->net_pay, 2) }}</td>
                        <td>
                            <x-filament::badge :color="$payslip->payment_status === 'Paid' ? 'success' : 'warning'">
                                {{ $payslip->payment_status }}
                            </x-filament::badge>
                        </td>
                        <td>
                            <button type="button" wire:click="streamPayslipPdf({{ $payslip->id }})" class="text-primary-600 hover:underline text-xs font-semibold">
                                Download
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="py-6 text-center text-gray-400">No payslips generated yet — click "Process Payroll" above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
