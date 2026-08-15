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
        .expired { color: #DC2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>
    <table>
        <thead><tr><th>Employee</th><th>Type</th><th>Date</th><th>Expiry</th><th>Provider</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($training as $t)
                <tr>
                    <td>{{ $t->staff?->full_name ?? '—' }}</td>
                    <td>{{ $t->training_type }}</td>
                    <td>{{ $t->date->format('d M Y') }}</td>
                    <td class="{{ $t->expiry_date && $t->expiry_date->isPast() ? 'expired' : '' }}">{{ $t->expiry_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $t->provider ?? '—' }}</td>
                    <td>{{ $t->status }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No training records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
