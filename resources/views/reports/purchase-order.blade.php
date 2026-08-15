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
        .kv { width: 100%; }
        .kv td { border: none; padding: 4px 8px; }
        .kv td:first-child { color: #64748b; width: 40%; }
        .totals { width: 40%; margin-left: auto; margin-top: 10px; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .grand-total { font-weight: bold; font-size: 14px; border-top: 2px solid #2f4f4f; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>Purchase Order {{ $order->po_number }} — Generated on {{ $date }}</p>
    </div>

    <div style="display:flex; justify-content:space-between;">
        <div style="width:48%;">
            <h2>Supplier</h2>
            <table class="kv">
                <tr><td>Company</td><td>{{ $order->supplier->company_name }}</td></tr>
                <tr><td>Contact</td><td>{{ $order->supplier->contact_person ?? '—' }}</td></tr>
                <tr><td>Phone</td><td>{{ $order->supplier->phone ?? '—' }}</td></tr>
                <tr><td>Email</td><td>{{ $order->supplier->email ?? '—' }}</td></tr>
            </table>
        </div>
        <div style="width:48%;">
            <h2>Order Details</h2>
            <table class="kv">
                <tr><td>PO Number</td><td>{{ $order->po_number }}</td></tr>
                <tr><td>Status</td><td>{{ $order->approval_status }}</td></tr>
                <tr><td>Payment Terms</td><td>{{ $order->payment_terms ?? '—' }}</td></tr>
                <tr><td>Expected Delivery</td><td>{{ $order->expected_delivery?->format('d M Y') ?? '—' }}</td></tr>
                <tr><td>Delivery Address</td><td>{{ $order->delivery_address ?? '—' }}</td></tr>
            </table>
        </div>
    </div>

    <h2>Line Items</h2>
    <table>
        <thead><tr><th>Material</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->material?->name }}</td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ $order->currency }} {{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $order->currency }} {{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td>{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</td></tr>
        <tr><td>Tax</td><td>{{ $order->currency }} {{ number_format($order->tax_amount, 2) }}</td></tr>
        <tr><td>Discount</td><td>-{{ $order->currency }} {{ number_format($order->discount_amount, 2) }}</td></tr>
        <tr class="grand-total"><td>Total</td><td>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</td></tr>
    </table>

    @if ($order->notes)
        <h2>Notes</h2>
        <p>{{ $order->notes }}</p>
    @endif
</body>
</html>
