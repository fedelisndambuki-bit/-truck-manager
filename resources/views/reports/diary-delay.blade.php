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
        .badge-low { background: #dcfce7; color: #16a34a; }
        .badge-medium { background: #fef3c7; color: #d97706; }
        .badge-high { background: #ffedd5; color: #c2410c; }
        .badge-critical { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>
    <table>
        <thead>
            <tr><th>Diary #</th><th>Site</th><th>Type</th><th>Reason</th><th>Duration</th><th>Impact</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($delays as $delay)
                <tr>
                    <td>{{ $delay->diary?->diary_number ?? '—' }}</td>
                    <td>{{ $delay->diary?->site?->name ?? '—' }}</td>
                    <td>{{ $delay->type }}</td>
                    <td>{{ $delay->reason }}</td>
                    <td>{{ $delay->duration_hours }} hrs</td>
                    <td><span class="badge badge-{{ strtolower($delay->impact) }}">{{ $delay->impact }}</span></td>
                    <td>{{ $delay->status }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No delays recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
