@php use App\Filament\Resources\InvoiceResource; @endphp
@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
@endpush
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>
    <style>
        .page-header-custom{background-color:#2f4f4f;padding:25px 35px;color:#fff;border-radius:10px;margin-bottom:20px}
        .page-header-content{display:flex;align-items:center;gap:12px}
        .page-header-icon{background:rgba(255,255,255,.15);padding:10px;border-radius:8px;display:flex;align-items:center;justify-content:center}
        .page-header-custom h1{font-size:24px;font-weight:600;margin-bottom:4px}
        .page-header-custom p{font-size:13px;opacity:.85;font-weight:400}
        .table-section{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden;margin-top:20px}
        .table-header-custom{background-color:#2f4f4f;color:#f9fafb;padding:15px 25px;display:flex;justify-content:space-between;align-items:center;border-radius:10px 10px 0 0}
        .table-title{font-size:15px;font-weight:600;color:#f9fafb;display:flex;align-items:center;gap:8px}
        .table-results{font-size:13px;color:rgba(249,250,251,.9)}
        .table-results strong{color:#fff}
        table{width:100%;border-collapse:collapse}
        thead{background:#f9fafb}
        thead th{padding:14px 20px;text-align:left;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e5e7eb}
        tbody tr{border-bottom:1px solid #f3f4f6;transition:background-color .15s}
        tbody tr:hover{background-color:#f9fafb}
        tbody td{padding:16px 20px;font-size:13px;color:#374151}
        .invoice-id{color:#3b82f6;font-weight:600;display:flex;align-items:center;gap:6px;cursor:pointer}
        .invoice-id:hover{text-decoration:underline}
        .amount{font-weight:600;font-size:14px}
        .amount.positive{color:#10b981}
        .amount.negative{color:#ef4444}
        .status-badge{display:inline-block;padding:5px 12px;border-radius:12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
        .status-badge.draft{background:#fef3c7;color:#92400e}
        .status-badge.sent{background:#dbeafe;color:#1e40af}
        .status-badge.paid{background:#d1fae5;color:#065f46}
        .status-badge.overdue{background:#fee2e2;color:#991b1b}
        .actions{display:flex;gap:8px}
        .action-btn{width:32px;height:32px;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;font-size:14px;background-color:rgba(59, 130, 246, 0.1) !important;color:#3b82f6 !important}
        .action-btn:hover{transform:translateY(-2px);box-shadow:0 2px 4px rgba(0, 0, 0, 0.1);background-color:rgba(59, 130, 246, 0.2) !important}

        /* Export Buttons Styling - Reverted */
        .btn-export {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-export.excel {
            background-color: #10b981 !important;
            color: white !important;
        }

        .btn-export.pdf {
            background-color: #fff !important;
            color: #3b82f6 !important;
            border-color: #3b82f6 !important;
        }

        .btn-export:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            opacity: 0.9;
        }

        .export-buttons {
            display: flex;
            gap: 12px;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-title {
            font-size: 16px;
            font-weight: 600;
            color: #2f4f4f;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .no-results-cell {
            text-align: center;
            padding: 60px !important;
            background-color: #fff !important;
            color: #2f4f4f !important;
            border-radius: 0 0 10px 10px;
        }

        .no-results-cell i {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
            opacity: 0.8;
            color: #2f4f4f !important;
        }

        .no-results-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .no-results-msg {
            font-size: 14px;
            opacity: 0.8;
        }
    </style>

    <div class="filament-tables-container rounded-xl flex flex-col gap-6 mb-6">
        @livewire(\App\Filament\Resources\InvoiceResource\Widgets\InvoiceHeaderWidget::class)
        @livewire(\App\Filament\Resources\InvoiceResource\Widgets\InvoiceFilterWidget::class)
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h7l4 4v9a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"/></svg>
                Invoice List
            </div>
            <div class="table-results">
                @php
                    $firstItem = $this->getTable()->getRecords()->firstItem();
                    $lastItem = $this->getTable()->getRecords()->lastItem();
                    $total = $this->getTable()->getRecords()->total();
                @endphp
                @if ($firstItem && $lastItem && $total)
                    Showing <strong>{{ $firstItem }}-{{ $lastItem }}</strong> of <strong>{{ $total }}</strong> results
                @else
                    No results
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Invoice No.</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Route</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->getTable()->getRecords() as $invoice)
                <tr>
                    <td>
                        <div class="invoice-id">
                            <a href="{{ InvoiceResource::getUrl('view', ['record' => $invoice->id]) }}">
                                {{ $invoice->invoice_number }}
                            </a>
                        </div>
                    </td>
                    <td>{{ optional($invoice->invoice_date)->format('M d, Y') }}</td>
                    <td>{{ optional($invoice->client)->company_name }}</td>
                    <td>{{ $invoice->route_from }} → {{ $invoice->route_to }}</td>
                    @php $amount = (float) ($invoice->total_amount ?? 0); @endphp
                    <td>
                        <span class="amount {{ $amount >= 0 ? 'positive' : 'negative' }}">
                            ${{ number_format($amount, 2) }}
                        </span>
                    </td>
                    @php $s = strtolower($invoice->status ?? 'draft'); @endphp
                    <td><span class="status-badge {{ $s }}">{{ strtoupper($invoice->status ?? 'draft') }}</span></td>
                    <td>
                        <div class="actions">
                            <a class="action-btn view" title="View" href="{{ InvoiceResource::getUrl('view', ['record' => $invoice->id]) }}">👁</a>
                            <a class="action-btn edit" title="Edit" href="{{ InvoiceResource::getUrl('edit', ['record' => $invoice->id]) }}">✎</a>
                            <a class="action-btn download" title="Download PDF" href="{{ route('invoices.pdf', ['invoice' => $invoice->id]) }}" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="no-results-cell">
                        <i class="fas fa-file-invoice"></i>
                        <div class="no-results-title">No results</div>
                        <div class="no-results-msg">No invoices found matching your criteria</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($this->getTable()->getRecords()->hasPages())
        <div style="padding:20px 25px;border-top:1px solid #f3f4f6;">
            {{ $this->getTable()->getRecords()->links() }}
        </div>
        @endif
    </div>
</x-filament-panels::page>
