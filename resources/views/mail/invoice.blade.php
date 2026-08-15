# Invoice {{ $invoice->invoice_number }}

Dear {{ $invoice->client->name }},

Please find attached your invoice for the recent services.

**Invoice Number:** {{ $invoice->invoice_number }}
**Invoice Date:** {{ $invoice->invoice_date->format('M d, Y') }}
**Due Date:** {{ $invoice->due_date->format('M d, Y') }}
**Total Amount:** {{ number_format($invoice->total_amount, 2) }}

Thank you for your business!

Regards,
Truck Manager Inc.
