<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2f4f4f; padding-bottom: 10px; }
        .header h1 { color: #2f4f4f; margin: 0; text-transform: uppercase; }
        .header p { margin: 5px 0; color: #666; }
        .kpi-row { display: table; width: 100%; margin-bottom: 20px; }
        .kpi { display: table-cell; text-align: center; padding: 8px; border: 1px solid #eee; }
        .kpi .label { font-size: 9px; text-transform: uppercase; color: #888; }
        .kpi .value { font-size: 14px; font-weight: bold; color: #2f4f4f; }
        h2.section-title { font-size: 13px; color: #2f4f4f; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-top: 22px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #2f4f4f; color: white; text-align: left; padding: 6px 8px; text-transform: uppercase; font-size: 9px; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        tr:nth-child(even) { background: #fcfcfc; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} &mdash; Generated on {{ $date }}</p>
    </div>

    @if (! empty($kpis))
        <div class="kpi-row">
            @foreach ($kpis as $kpi)
                <div class="kpi">
                    <div class="label">{{ $kpi['label'] }}</div>
                    <div class="value">{{ $kpi['value'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @foreach ($sections as $section)
        @if (!empty($section['title']))
            <h2 class="section-title">{{ $section['title'] }}</h2>
        @endif
        <table>
            <thead>
                <tr>
                    @foreach ($section['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($section['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($section['columns']) }}">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
