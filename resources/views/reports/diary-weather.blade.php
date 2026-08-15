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
        .badge-none { background: #dcfce7; color: #16a34a; }
        .badge-minor { background: #fef3c7; color: #d97706; }
        .badge-moderate { background: #ffedd5; color: #c2410c; }
        .badge-severe { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }} (last 30 days)</p>
    </div>
    <table>
        <thead>
            <tr><th>Date</th><th>Site</th><th>Conditions</th><th>Temp</th><th>Rainfall</th><th>Wind</th><th>Humidity</th><th>Impact</th></tr>
        </thead>
        <tbody>
            @forelse ($diaries as $diary)
                <tr>
                    <td>{{ $diary->date?->format('d M Y') }}</td>
                    <td>{{ $diary->site?->name ?? '—' }}</td>
                    <td>{{ $diary->conditions ?? '—' }}</td>
                    <td>{{ $diary->temperature_high ?? '—' }}°C</td>
                    <td>{{ $diary->rainfall_mm ?? 0 }} mm</td>
                    <td>{{ $diary->wind_speed_kph ?? 0 }} kph</td>
                    <td>{{ $diary->humidity_pct ?? 0 }}%</td>
                    <td><span class="badge badge-{{ strtolower($diary->weather_impact ?? 'none') }}">{{ $diary->weather_impact ?? 'None' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8">No weather data found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
