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
        .stats-container { width: 100%; margin-bottom: 25px; }
        .stat-box { width: 22%; display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; text-align: center; border-radius: 8px; margin-right: 6px; }
        .stat-value { font-size: 18px; font-weight: bold; color: #2f4f4f; }
        .stat-label { font-size: 9px; color: #64748b; text-transform: uppercase; margin-top: 5px; }
        h2 { color: #2f4f4f; border-left: 4px solid #2f4f4f; padding-left: 10px; margin-top: 20px; margin-bottom: 12px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2f4f4f; color: white; text-align: left; padding: 8px; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #fcfcfc; }
        .kv { width: 100%; }
        .kv td { border: none; padding: 4px 8px; }
        .kv td:first-child { color: #64748b; width: 40%; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>

    <div class="stats-container">
        <div class="stat-box">
            <div class="stat-value">{{ $equipment->status }}</div>
            <div class="stat-label">Status</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $equipment->equipment_type }}</div>
            <div class="stat-label">Category</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ number_format($equipment->engine_hours ?? 0, 0) }}</div>
            <div class="stat-label">Engine Hours</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $equipment->registration_number ?? '—' }}</div>
            <div class="stat-label">Registration</div>
        </div>
    </div>

    <h2>Equipment Details</h2>
    <table class="kv">
        <tr><td>Equipment Code</td><td>{{ $equipment->equipment_code }}</td></tr>
        <tr><td>Serial Number</td><td>{{ $equipment->serial_number ?? '—' }}</td></tr>
        <tr><td>Purchase Date</td><td>{{ $equipment->purchase_date?->format('d M Y') ?? '—' }}</td></tr>
        <tr><td>Insurance Provider</td><td>{{ $equipment->insurance_provider ?? '—' }}</td></tr>
        <tr><td>Insurance Expiry</td><td>{{ $equipment->insurance_expiry?->format('d M Y') ?? '—' }}</td></tr>
        <tr><td>Warranty Expiry</td><td>{{ $equipment->warranty_expiry?->format('d M Y') ?? '—' }}</td></tr>
    </table>

    <h2>Allocation History</h2>
    <table>
        <thead><tr><th>Project</th><th>Site</th><th>Operator</th><th>From</th><th>To</th><th>Hours</th></tr></thead>
        <tbody>
            @forelse ($equipment->allocations()->with(['project', 'site', 'operator'])->latest('allocated_from')->limit(15)->get() as $a)
                <tr>
                    <td>{{ $a->project?->name ?? '—' }}</td>
                    <td>{{ $a->site?->name ?? '—' }}</td>
                    <td>{{ $a->operator?->full_name ?? '—' }}</td>
                    <td>{{ $a->allocated_from?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $a->allocated_to?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $a->operating_hours_today }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No allocation history.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Maintenance History</h2>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Cost</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($equipment->maintenances()->latest('maintenance_date')->limit(10)->get() as $m)
                <tr>
                    <td>{{ $m->maintenance_date?->format('d M Y') }}</td>
                    <td>{{ $m->maintenance_type }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($m->description, 40) }}</td>
                    <td>{{ number_format($m->cost ?? 0, 2) }}</td>
                    <td>{{ $m->status }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No maintenance records.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
