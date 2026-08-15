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
        <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>UOM</th><th>On Hand</th><th>Reorder Level</th><th>Status</th><th>Preferred Supplier</th></tr></thead>
        <tbody>
            @forelse ($materials as $m)
                <tr>
                    <td>{{ $m->material_code }}</td>
                    <td>{{ $m->name }}</td>
                    <td>{{ $m->category?->name ?? '—' }}</td>
                    <td>{{ $m->unit }}</td>
                    <td>{{ number_format($m->quantity_on_hand, 2) }}</td>
                    <td>{{ $m->reorder_level }}</td>
                    <td>{{ $m->stock_status }}</td>
                    <td>{{ $m->preferredSupplier?->company_name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No materials found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
