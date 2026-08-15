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
                <th>Delivery #</th>
                <th>Material</th>
                <th>Supplier</th>
                <th>Site</th>
                <th>Quantity</th>
                <th>Delivered</th>
                <th>Status</th>
                <th>Inspection</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deliveries as $delivery)
                <tr>
                    <td>{{ $delivery->delivery_number }}</td>
                    <td>{{ $delivery->material?->name ?? '—' }}</td>
                    <td>{{ $delivery->supplier?->company_name ?? $delivery->supplier_name ?? '—' }}</td>
                    <td>{{ $delivery->site?->name ?? '—' }}</td>
                    <td>{{ $delivery->quantity }}</td>
                    <td>{{ $delivery->delivered_at?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $delivery->status }}</td>
                    <td>{{ $delivery->goodsReceivedNote?->status ?? 'Not Inspected' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No deliveries found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
