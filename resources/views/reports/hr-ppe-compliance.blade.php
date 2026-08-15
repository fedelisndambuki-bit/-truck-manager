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
        <thead>
            <tr>
                <th>Employee</th>
                <th>Item</th>
                <th>Size</th>
                <th>Issue Date</th>
                <th>Replacement Due</th>
                <th>Cost</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($issuances as $issuance)
                <tr>
                    <td>{{ $issuance->staff?->full_name ?? '—' }}</td>
                    <td>{{ $issuance->item }}</td>
                    <td>{{ $issuance->size ?? '—' }}</td>
                    <td>{{ $issuance->issue_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $issuance->replacement_due_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $currency }} {{ number_format($issuance->cost ?? 0, 2) }}</td>
                    <td>{{ $issuance->status }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No PPE issuances found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
