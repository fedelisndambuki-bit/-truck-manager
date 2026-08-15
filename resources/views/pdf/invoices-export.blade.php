<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoices Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h2 { margin: 0 0 4px 0; color: #2f4f4f; font-size: 16px; }
        .meta { margin: 0 0 14px 0; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #2f4f4f; padding: 6px 8px; text-align: left; }
        th { background-color: #2f4f4f; color: #fff; font-weight: 600; font-size: 10px; text-transform: uppercase; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h2>Invoices Export</h2>
    <p class="meta">Exported on {{ now()->format('M d, Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Date</th>
                <th>Due Date</th>
                <th>Client</th>
                <th>Route</th>
                <th class="num">Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ optional($invoice->invoice_date)->format('Y-m-d') }}</td>
                    <td>{{ optional($invoice->due_date)->format('Y-m-d') }}</td>
                    <td>{{ optional($invoice->client)->company_name ?? optional($invoice->client)->name }}</td>
                    <td>{{ trim(($invoice->route_from ?? '') . ' -> ' . ($invoice->route_to ?? '')) }}</td>
                    <td class="num">{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</td>
                    <td>{{ strtoupper((string) ($invoice->status ?? 'draft')) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No invoices found for selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
