<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $ticketTitle }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #2f4f4f; padding-bottom: 10px; }
        .header h1 { color: #2f4f4f; margin: 0; text-transform: uppercase; }
        .header p { margin: 5px 0; color: #666; }
        .ticket-meta { display: table; width: 100%; margin-bottom: 14px; }
        .ticket-meta .col { display: table-cell; width: 50%; vertical-align: top; }
        .qr { text-align: right; }
        .qr img, .qr svg { width: 110px; height: 110px; }
        table.fields { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.fields td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        table.fields td.label { color: #888; text-transform: uppercase; font-size: 9px; width: 40%; }
        .footer { margin-top: 24px; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub</h1>
        <p>{{ $ticketTitle }} &mdash; Generated on {{ $date }}</p>
    </div>

    <div class="ticket-meta">
        <div class="col">
            <strong>{{ $documentNumber }}</strong>
        </div>
        <div class="col qr">
            @if (! empty($qrCode))
                {!! $qrCode !!}
            @endif
        </div>
    </div>

    <table class="fields">
        @foreach ($fields as $field)
            <tr>
                <td class="label">{{ $field['label'] }}</td>
                <td>{{ $field['value'] ?? '—' }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer">
        This document was generated automatically by Tanaki Hub Smart Weighbridge. No signature required unless indicated.
    </div>
</body>
</html>
