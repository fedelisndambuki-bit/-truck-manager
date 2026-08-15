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
    </div>
    <table>
        <thead><tr><th>Company</th><th>Insurance Expiry</th><th>License Expiry</th><th>NCA Expiry</th><th>Tax Compliance</th><th>Overall Status</th></tr></thead>
        <tbody>
            @forelse ($subcontractors as $s)
                <tr>
                    <td>{{ $s->company_name }}</td>
                    <td>{{ $s->insurance_expiry_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $s->business_license_expiry_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $s->nca_expiry_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $s->tax_compliance_status ?? '—' }}</td>
                    <td>{{ $s->compliance_status }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No subcontractors found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
