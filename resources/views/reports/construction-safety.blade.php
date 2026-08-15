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
        .badge-high { background: #fee2e2; color: #dc2626; }
        .badge-critical { background: #fecaca; color: #991b1b; }
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
                <th>Date</th>
                <th>Site</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Reported By</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($incidents as $incident)
                <tr>
                    <td>{{ $incident->date?->format('d M Y') }}</td>
                    <td>{{ $incident->site?->name ?? '—' }}</td>
                    <td><span class="badge badge-{{ strtolower($incident->severity) }}">{{ $incident->severity }}</span></td>
                    <td>{{ $incident->status }}</td>
                    <td>{{ $incident->reportedBy?->full_name ?? '—' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($incident->description, 60) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
