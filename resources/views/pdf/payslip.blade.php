<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; margin: 24px; }
        .title-row { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .title-row td { vertical-align: top; }
        .company-name { font-size: 38px; font-weight: 800; color: #2f4f4f; letter-spacing: 0.3px; margin-bottom: 2px; }
        .company-meta { color: #334155; line-height: 1.3; font-size: 11px; }
        .payslip-title { text-align: right; font-size: 42px; font-weight: 900; color: #2f4f4f; letter-spacing: 1px; }

        .section-title { background: #2f4f4f; color: #ffffff; font-weight: 700; font-size: 10px; padding: 5px 8px; text-transform: uppercase; }
        .meta-table, .grid-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta-table td { border: 1px solid #d1d5db; padding: 5px 7px; }
        .meta-head { background: #4f7292; color: #ffffff; font-weight: 700; text-transform: uppercase; font-size: 9px; text-align: center; }
        .meta-value { background: #f8fafc; text-align: center; font-size: 10px; font-weight: 700; }
        .employee-cell { border: 1px solid #d1d5db; padding: 8px; }
        .employee-name { font-size: 13px; font-weight: 700; margin-bottom: 3px; color: #0f172a; }
        .employee-line { font-size: 10px; color: #334155; line-height: 1.35; }
        .pay-method { margin: 4px 0 10px; font-size: 10px; }
        .pay-method strong { color: #2f4f4f; }

        .grid-table th, .grid-table td { border: 1px solid #cbd5e1; padding: 5px 6px; }
        .grid-table th { background: #e5e7eb; color: #0f172a; text-transform: uppercase; font-size: 9px; text-align: left; }
        .grid-table td { font-size: 10px; }
        .num { text-align: right; }
        .summary-row td { background: #e5e7eb; font-weight: 700; font-size: 12px; }
        .net-row td { background: #dbe4ea; font-weight: 800; font-size: 13px; }
        .mute-note { color: #64748b; font-size: 10px; margin: 8px 0 0; }
        .footer-note { margin-top: 16px; text-align: center; font-size: 10px; color: #334155; }
    </style>
</head>
<body>
@php
    $canRenderImages = $canRenderImages ?? true;
    $companyName = $companyDetails['name'] ?? config('app.name');
    $contactLine = trim(implode(', ', array_filter([
        $companyDetails['phone'] ?? null,
        $companyDetails['email'] ?? null,
    ])));
    $addressLine = $companyDetails['address'] ?? '';
    $taxLine = $companyDetails['tax_id'] ?? '';
    $employeeName = $employee->full_name ?? 'Driver';
    $employeeAddress = $employee->address ?? '-';
    $employeePhone = $employee->phone_number ?? '-';
    $employeeEmail = $employee->email ?? '-';

    $earningsRows = [
        ['label' => 'Standard Pay', 'key' => 'standard_pay'],
        ['label' => 'Overtime Pay', 'key' => 'overtime_pay'],
        ['label' => 'Holiday Pay', 'key' => 'holiday_pay'],
        ['label' => 'Basic Pay', 'key' => 'basic_pay'],
        ['label' => 'Commission and Bonus', 'key' => 'commission_bonus'],
        ['label' => 'Sick Pay', 'key' => 'sick_pay'],
        ['label' => 'Expenses', 'key' => 'expenses'],
    ];

    $deductionRows = [
        ['label' => 'PAYE Tax', 'key' => 'paye_tax'],
        ['label' => 'National Insurance', 'key' => 'national_insurance'],
        ['label' => 'Pension', 'key' => 'pension'],
        ['label' => 'Withholding Tax', 'key' => 'withholding_tax'],
    ];
@endphp

<table class="title-row">
    <tr>
        <td style="width: 72%;">
            <div class="company-name">{{ $companyName }}</div>
            <div class="company-meta">
                @if($addressLine) {{ $addressLine }}<br>@endif
                @if($contactLine) {{ $contactLine }}<br>@endif
                @if($taxLine) Tax ID: {{ $taxLine }} @endif
            </div>
        </td>
        <td style="width: 28%;">
            <div class="payslip-title">PAYSLIP</div>
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td style="width: 48%; vertical-align: top; padding: 0;">
            <div class="section-title">Employee Information</div>
            <div class="employee-cell">
                <div class="employee-name">{{ $employeeName }}</div>
                <div class="employee-line">{{ $employeeAddress }}</div>
                <div class="employee-line">Phone: {{ $employeePhone }}</div>
                <div class="employee-line">Email: {{ $employeeEmail }}</div>
            </div>
        </td>
        <td style="width: 52%; padding: 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td class="meta-head">Pay Date</td>
                    <td class="meta-head">Pay Type</td>
                    <td class="meta-head">Period</td>
                </tr>
                <tr>
                    <td class="meta-value">{{ $payMeta['pay_date'] }}</td>
                    <td class="meta-value">{{ $payMeta['pay_type'] }}</td>
                    <td class="meta-value">{{ $payMeta['period'] }}</td>
                </tr>
                <tr>
                    <td class="meta-head">Payroll #</td>
                    <td class="meta-head">ID Number</td>
                    <td class="meta-head">Tax Code</td>
                </tr>
                <tr>
                    <td class="meta-value">{{ $payMeta['payroll_number'] }}</td>
                    <td class="meta-value">{{ $payMeta['ni_number'] }}</td>
                    <td class="meta-value">{{ $payMeta['tax_code'] }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="pay-method"><strong>Payment Method:</strong> {{ $payMeta['payment_method'] }}</div>

<table class="grid-table">
    <tr>
        <th style="width: 34%;">Earnings</th>
        <th style="width: 12%;">Hours</th>
        <th style="width: 14%;">Rate</th>
        <th style="width: 20%;" class="num">Current</th>
        <th style="width: 20%;" class="num">YTD</th>
    </tr>
    @foreach($earningsRows as $row)
        @php
            $currentAmount = (float) ($earningsCurrent[$row['key']] ?? 0);
            $ytdAmount = (float) ($earningsYtd[$row['key']] ?? 0);
            $hours = (float) ($earningHoursCurrent[$row['key']] ?? 0);
            $rate = (float) ($earningRatesCurrent[$row['key']] ?? 0);
        @endphp
        <tr>
            <td>{{ $row['label'] }}</td>
            <td class="num">{{ $hours > 0 ? number_format($hours, 2) : '-' }}</td>
            <td class="num">{{ $rate > 0 ? number_format($rate, 2) : '-' }}</td>
            <td class="num">{{ $currentAmount > 0 ? number_format($currentAmount, 2) : '-' }}</td>
            <td class="num">{{ $ytdAmount > 0 ? number_format($ytdAmount, 2) : '-' }}</td>
        </tr>
    @endforeach
    <tr class="summary-row">
        <td colspan="3" class="num">GROSS PAY</td>
        <td class="num">{{ number_format((float) $grossCurrent, 2) }}</td>
        <td class="num">{{ number_format((float) $grossYtd, 2) }}</td>
    </tr>
</table>

<table class="grid-table">
    <tr>
        <th style="width: 60%;">Deductions</th>
        <th style="width: 20%;" class="num">Current</th>
        <th style="width: 20%;" class="num">YTD</th>
    </tr>
    @foreach($deductionRows as $row)
        @php
            $currentAmount = (float) ($deductionsCurrent[$row['key']] ?? 0);
            $ytdAmount = (float) ($deductionsYtd[$row['key']] ?? 0);
        @endphp
        <tr>
            <td>{{ $row['label'] }}</td>
            <td class="num">{{ $currentAmount > 0 ? number_format($currentAmount, 2) : '-' }}</td>
            <td class="num">{{ $ytdAmount > 0 ? number_format($ytdAmount, 2) : '-' }}</td>
        </tr>
    @endforeach
    <tr class="summary-row">
        <td class="num">TOTAL DEDUCTIONS</td>
        <td class="num">{{ number_format((float) $totalDeductionsCurrent, 2) }}</td>
        <td class="num">{{ number_format((float) $totalDeductionsYtd, 2) }}</td>
    </tr>
    <tr class="net-row">
        <td class="num">NET PAY</td>
        <td class="num">{{ number_format((float) $netCurrent, 2) }}</td>
        <td class="num">{{ number_format((float) $netYtd, 2) }}</td>
    </tr>
</table>

@if (! $canRenderImages)
    <div class="mute-note">Images were omitted because the server GD extension is not available.</div>
@endif

<div class="footer-note">
    If you have any questions about this payslip, please contact: {{ $contactLine ?: 'HR/Payroll' }}
</div>
</body>
</html>
