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
        .kv { width: 100%; }
        .kv td { border: none; padding: 4px 8px; }
        .kv td:first-child { color: #64748b; width: 40%; }
        .kv td:last-child { text-align: right; }
        .total-row td { font-weight: bold; border-top: 1px solid #2f4f4f; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>Payment Certificate {{ $certificate->certificate_number }} — Generated on {{ $date }}</p>
    </div>

    <div style="display:flex; justify-content:space-between;">
        <div style="width:48%;">
            <h2>Subcontractor</h2>
            <table class="kv">
                <tr><td>Company</td><td>{{ $certificate->contract->subcontractor->company_name }}</td></tr>
                <tr><td>Contract No.</td><td>{{ $certificate->contract->contract_number }}</td></tr>
            </table>
        </div>
        <div style="width:48%;">
            <h2>Certificate Details</h2>
            <table class="kv">
                <tr><td>Type</td><td>{{ $certificate->certificate_type }}</td></tr>
                <tr><td>Period Covered</td><td>{{ $certificate->period_covered ?? '—' }}</td></tr>
                <tr><td>Status</td><td>{{ $certificate->status }}</td></tr>
            </table>
        </div>
    </div>

    <h2>Valuation</h2>
    <table class="kv">
        <tr><td>Gross Amount</td><td>{{ $currency }} {{ number_format($certificate->gross_amount, 2) }}</td></tr>
        <tr><td>Retention ({{ $certificate->contract->retention_percent }}%)</td><td>-{{ $currency }} {{ number_format($certificate->retention_amount, 2) }}</td></tr>
        <tr><td>Advance Recovery</td><td>-{{ $currency }} {{ number_format($certificate->advance_recovery_amount, 2) }}</td></tr>
        <tr><td>Previously Certified</td><td>-{{ $currency }} {{ number_format($certificate->previous_certified_amount, 2) }}</td></tr>
        <tr class="total-row"><td>Net Amount Due</td><td>{{ $currency }} {{ number_format($certificate->net_amount_due, 2) }}</td></tr>
    </table>

    <h2>Approval & Payment</h2>
    <table class="kv">
        <tr><td>Approved Amount</td><td>{{ $currency }} {{ number_format($certificate->approved_amount, 2) }}</td></tr>
        <tr><td>Paid Amount</td><td>{{ $currency }} {{ number_format($certificate->paid_amount, 2) }}</td></tr>
        <tr><td>Outstanding Balance</td><td>{{ $currency }} {{ number_format($certificate->outstanding_balance, 2) }}</td></tr>
        <tr><td>Approved Date</td><td>{{ $certificate->approved_at?->format('d M Y') ?? '—' }}</td></tr>
    </table>
</body>
</html>
