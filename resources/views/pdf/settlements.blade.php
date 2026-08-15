<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Settlements Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #2f4f4f; padding: 6px 8px; text-align: left; }
        th { background-color: #2f4f4f; color: #fff; font-weight: 600; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .header { margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 16px; color: #2f4f4f; }
        .header p { margin: 4px 0 0; color: #6b7280; }
        .amount-positive { color: #059669; }
        .amount-negative { color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Settlements & Payments</h1>
        <p>Exported on {{ now()->format('M d, Y H:i') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Settlement ID</th>
                <th>Settlement Date</th>
                <th>Pay Period</th>
                <th>Trucks</th>
                <th>Trips</th>
                <th>Net Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($settlements as $s)
            <tr>
                <td>{{ $s->settlement_id }}</td>
                <td>{{ $s->settlement_date->format('M d, Y') }}</td>
                <td>{{ $s->pay_period }}</td>
                <td>{{ $s->trucks }}</td>
                <td>{{ $s->trips }}</td>
                <td class="{{ $s->net_amount >= 0 ? 'amount-positive' : 'amount-negative' }}">${{ number_format($s->net_amount, 2) }}</td>
                <td>{{ $s->status }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center;">No settlements found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
