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
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>Subcontract Agreement {{ $contract->contract_number }} — Generated on {{ $date }}</p>
    </div>

    <div style="display:flex; justify-content:space-between;">
        <div style="width:48%;">
            <h2>Subcontractor</h2>
            <table class="kv">
                <tr><td>Company</td><td>{{ $contract->subcontractor->company_name }}</td></tr>
                <tr><td>Registration No.</td><td>{{ $contract->subcontractor->registration_number ?? '—' }}</td></tr>
                <tr><td>Contact</td><td>{{ $contract->subcontractor->contact_person ?? '—' }}</td></tr>
            </table>
        </div>
        <div style="width:48%;">
            <h2>Project</h2>
            <table class="kv">
                <tr><td>Project</td><td>{{ $contract->project->name }}</td></tr>
                <tr><td>Site</td><td>{{ $contract->site?->name ?? '—' }}</td></tr>
                <tr><td>Status</td><td>{{ $contract->status }}</td></tr>
            </table>
        </div>
    </div>

    <h2>Scope of Work</h2>
    <p>{{ $contract->scope_summary ?? 'No scope summary recorded.' }}</p>

    <h2>Commercial Terms</h2>
    <table class="kv">
        <tr><td>Contract Value</td><td>{{ $currency }} {{ number_format($contract->contract_value, 2) }}</td></tr>
        <tr><td>Retention</td><td>{{ $contract->retention_percent }}%</td></tr>
        <tr><td>Payment Terms</td><td>{{ $contract->payment_terms ?? '—' }}</td></tr>
        <tr><td>Performance Bond</td><td>{{ $contract->performance_bond_amount ? $currency . ' ' . number_format($contract->performance_bond_amount, 2) : '—' }} ({{ $contract->performance_bond_provider ?? '—' }})</td></tr>
        <tr><td>Insurance Required</td><td>{{ $contract->insurance_required ? 'Yes' : 'No' }}</td></tr>
    </table>

    <h2>Programme</h2>
    <table class="kv">
        <tr><td>Start Date</td><td>{{ $contract->start_date?->format('d M Y') ?? '—' }}</td></tr>
        <tr><td>Completion Date</td><td>{{ $contract->completion_date?->format('d M Y') ?? '—' }}</td></tr>
        <tr><td>Signed Date</td><td>{{ $contract->signed_date?->format('d M Y') ?? '—' }}</td></tr>
    </table>
</body>
</html>
