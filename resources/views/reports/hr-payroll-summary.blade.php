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
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2f4f4f; color: white; text-align: left; padding: 8px; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #fcfcfc; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
        @if ($period)
            <p>Period: {{ \App\Filament\Resources\PayrollPeriodResource::getMonthOptions()[$period->month] }} {{ $period->year }} — {{ $period->status }}</p>
        @endif
    </div>
    @if (! $period)
        <p>No payroll periods have been processed yet.</p>
    @else
        <table>
            <thead><tr><th>Employee</th><th>Gross</th><th>NSSF</th><th>SHA</th><th>Housing Levy</th><th>PAYE</th><th>Net Pay</th></tr></thead>
            <tbody>
                @forelse ($payslips as $p)
                    <tr>
                        <td>{{ $p->staff?->full_name ?? '—' }}</td>
                        <td>{{ number_format($p->gross_pay, 2) }}</td>
                        <td>{{ number_format($p->nssf_amount, 2) }}</td>
                        <td>{{ number_format($p->sha_amount, 2) }}</td>
                        <td>{{ number_format($p->housing_levy_amount, 2) }}</td>
                        <td>{{ number_format($p->paye_amount, 2) }}</td>
                        <td>{{ number_format($p->net_pay, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No payslips found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
