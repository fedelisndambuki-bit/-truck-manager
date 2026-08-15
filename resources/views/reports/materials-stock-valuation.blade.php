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
        .total-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px; text-align: center; margin-bottom: 20px; }
        .total-box .value { font-size: 20px; font-weight: bold; color: #166534; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>

    <div class="total-box">
        <div>Total Inventory Value</div>
        <div class="value">{{ $currency }} {{ number_format($totalValue, 2) }}</div>
    </div>

    <table>
        <thead><tr><th>Code</th><th>Name</th><th>On Hand</th><th>Unit Cost</th><th>Value</th></tr></thead>
        <tbody>
            @forelse ($materials as $m)
                <tr>
                    <td>{{ $m->material_code }}</td>
                    <td>{{ $m->name }}</td>
                    <td>{{ number_format($m->quantity_on_hand, 2) }}</td>
                    <td>{{ number_format($m->average_cost ?? $m->unit_cost, 2) }}</td>
                    <td>{{ number_format($m->inventory_value, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No materials found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
