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
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { width: 25%; padding: 14px; border: 1px solid #e5e7eb; text-align: center; }
        .stat-value { font-size: 22px; font-weight: bold; color: #2f4f4f; }
        .stat-label { font-size: 10px; color: #666; text-transform: uppercase; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} &mdash; Generated on {{ $date }}</p>
    </div>

    <table class="grid">
        @foreach (array_chunk($stats, 4, true) as $chunk)
            <tr>
                @foreach ($chunk as $label => $value)
                    <td>
                        <div class="stat-value">{{ $value }}</div>
                        <div class="stat-label">{{ $label }}</div>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</body>
</html>
