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
        tr:nth-child(even) { background: #fcfcfc; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }} (last 30 days)</p>
    </div>

    @forelse ($bySupervisor as $supervisor => $diaries)
        <h2>{{ $supervisor }} — {{ $diaries->count() }} diaries</h2>
        <table>
            <thead><tr><th>Diary #</th><th>Site</th><th>Date</th><th>Status</th><th>Progress %</th></tr></thead>
            <tbody>
                @foreach ($diaries as $diary)
                    <tr>
                        <td>{{ $diary->diary_number }}</td>
                        <td>{{ $diary->site?->name ?? '—' }}</td>
                        <td>{{ $diary->date?->format('d M Y') }}</td>
                        <td>{{ $diary->status }}</td>
                        <td>{{ $diary->progress_pct ?? 0 }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>No diaries found in the last 30 days.</p>
    @endforelse
</body>
</html>
