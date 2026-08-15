<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2f4f4f; padding-bottom: 10px; }
        .header h1 { color: #2f4f4f; margin: 0; text-transform: uppercase; }
        .header p { margin: 5px 0; color: #666; }
        h2 { color: #2f4f4f; border-left: 4px solid #2f4f4f; padding-left: 10px; margin-top: 20px; margin-bottom: 12px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2f4f4f; color: white; text-align: left; padding: 8px; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .kv td { border: none; padding: 4px 8px; }
        .kv td:first-child { color: #64748b; width: 40%; }
        .net-pay { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px; text-align: center; margin-top: 10px; }
        .net-pay .value { font-size: 22px; font-weight: bold; color: #166534; }
        .net-pay .label { font-size: 10px; color: #64748b; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — {{ \App\Filament\Resources\PayrollPeriodResource::getMonthOptions()[$payslip->payrollPeriod->month] }} {{ $payslip->payrollPeriod->year }}</p>
    </div>

    <h2>Employee Details</h2>
    <table class="kv">
        <tr><td>Name</td><td>{{ $payslip->staff->full_name }}</td></tr>
        <tr><td>Staff Number</td><td>{{ $payslip->staff->staff_number }}</td></tr>
        <tr><td>KRA PIN</td><td>{{ $payslip->staff->kra_pin ?? '—' }}</td></tr>
        <tr><td>NSSF Number</td><td>{{ $payslip->staff->nssf_number ?? '—' }}</td></tr>
        <tr><td>Bank</td><td>{{ $payslip->staff->bank_name ?? '—' }} {{ $payslip->staff->bank_account_number ?? '' }}</td></tr>
    </table>

    <h2>Earnings</h2>
    <table>
        <thead><tr><th>Item</th><th>Amount ({{ $currency }})</th></tr></thead>
        <tbody>
            <tr><td>Basic Salary</td><td>{{ number_format($payslip->basic_salary, 2) }}</td></tr>
            <tr><td>Allowances</td><td>{{ number_format($payslip->allowances, 2) }}</td></tr>
            <tr><td>Overtime</td><td>{{ number_format($payslip->overtime_amount, 2) }}</td></tr>
            <tr><td><strong>Gross Pay</strong></td><td><strong>{{ number_format($payslip->gross_pay, 2) }}</strong></td></tr>
        </tbody>
    </table>

    <h2>Statutory Deductions</h2>
    <table>
        <thead><tr><th>Item</th><th>Amount ({{ $currency }})</th></tr></thead>
        <tbody>
            <tr><td>NSSF</td><td>{{ number_format($payslip->nssf_amount, 2) }}</td></tr>
            <tr><td>SHA (Social Health Authority)</td><td>{{ number_format($payslip->sha_amount, 2) }}</td></tr>
            <tr><td>Affordable Housing Levy</td><td>{{ number_format($payslip->housing_levy_amount, 2) }}</td></tr>
            <tr><td>PAYE</td><td>{{ number_format($payslip->paye_amount, 2) }}</td></tr>
            <tr><td>Loan / Salary Advance</td><td>{{ number_format($payslip->loan_deduction, 2) }}</td></tr>
            <tr><td>Other Deductions</td><td>{{ number_format($payslip->other_deductions, 2) }}</td></tr>
        </tbody>
    </table>

    <div class="net-pay">
        <div class="label">Net Pay</div>
        <div class="value">{{ $currency }} {{ number_format($payslip->net_pay, 2) }}</div>
    </div>
</body>
</html>
