<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th, td {
            border: 1px solid #2f4f4f;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #2f4f4f;
            color: #fff;
            font-weight: 600;
        }
        .header {
            margin-bottom: 20px;
            overflow: hidden;
            border-bottom: 2px solid #2f4f4f;
            padding-bottom: 10px;
        }
        .header .logo {
            float: left;
            width: 90px;
            height: 60px;
            object-fit: contain;
        }
        .header .company-details {
            float: right;
            text-align: right;
        }
        .header .company-details h3 {
            margin: 0;
            color: #2f4f4f;
            font-size: 16px;
        }
        .header .company-details p {
            margin: 2px 0 0;
            color: #6b7280;
        }
        .invoice-details {
            margin-bottom: 16px;
            overflow: hidden;
        }
        .invoice-details .client-info {
            float: left;
            width: 52%;
        }
        .invoice-details .invoice-info {
            float: right;
            text-align: right;
            width: 45%;
        }
        .invoice-details h4 {
            margin: 0 0 6px;
            color: #2f4f4f;
        }
        .invoice-details p {
            margin: 2px 0;
            color: #374151;
        }
        .line-items table {
            margin-top: 10px;
        }
        .line-items h3 {
            margin: 0 0 8px;
            color: #2f4f4f;
        }
        .total-section {
            width: 100%;
            text-align: right;
            margin-top: 10px;
        }
        .total-section table {
            width: 40%;
            float: right;
        }
        .total-section th,
        .total-section td {
            border: 1px solid #2f4f4f;
            padding: 6px 8px;
        }
        .total-section .total-row {
            font-weight: bold;
            background-color: #2f4f4f;
            color: #fff;
        }
        .line-items tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($companyLogoPath)
            <img src="{{ $companyLogoPath }}" class="logo">
        @endif
        <div class="company-details">
            <h3>{{ $company->name }}</h3>
            <p>{{ $company->address }}</p>
            <p>{{ $company->phone }}</p>
            <p>{{ $company->email }}</p>
        </div>
    </div>

    <div style="clear:both;"></div>

    <div class="invoice-details">
        <div class="client-info">
            <h4>Bill To:</h4>
            <p>{{ $invoice->client->name }}</p>
            <p>{{ $invoice->client->address ?? 'N/A' }}</p>
            <p>{{ $invoice->client->email ?? 'N/A' }}</p>
        </div>
        <div class="invoice-info">
            <h4>Invoice #{{ $invoice->invoice_number }}</h4>
            <p>Invoice Date: {{ $invoice->invoice_date->format('M d, Y') }}</p>
            <p>Due Date: {{ $invoice->due_date->format('M d, Y') }}</p>
            <p>Status: {{ ucfirst($invoice->status) }}</p>
        </div>
    </div>

    <div style="clear:both;"></div>

    <div class="line-items">
        <h3>Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->line_items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ number_format($item['rate'], 2) }}</td>
                    <td>{{ number_format($item['quantity'] * $item['rate'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="total-section">
        <table>
            <tr>
                <th>Subtotal:</th>
                <td>{{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <th>Tax (10%):</th>
                <td>{{ number_format($invoice->tax, 2) }}</td>
            </tr>
            <tr class="total-row">
                <th>Total:</th>
                <td>{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div style="clear:both;"></div>
</body>
</html>
