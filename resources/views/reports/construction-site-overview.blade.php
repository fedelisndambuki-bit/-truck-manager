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
        .badge { padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-inactive { background: #f1f5f9; color: #475569; }
        .badge-completed { background: #dbeafe; color: #2563eb; }
        .badge-onhold { background: #fef3c7; color: #d97706; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Site Code</th>
                <th>Site</th>
                <th>Project</th>
                <th>Location</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Phase</th>
                <th>Risk Level</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sites as $site)
                <tr>
                    <td>{{ $site->site_code }}</td>
                    <td>{{ $site->name }}</td>
                    <td>{{ $site->project?->name ?? '—' }}</td>
                    <td>{{ $site->location ?? '—' }}</td>
                    <td>
                        <span class="badge badge-{{ strtolower(str_replace(' ', '', $site->status ?? 'inactive')) }}">{{ $site->status ?? '—' }}</span>
                    </td>
                    <td>{{ $site->progress_percent }}%</td>
                    <td>{{ $site->current_phase ?? '—' }}</td>
                    <td>{{ $site->safety_risk_level ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No sites recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
