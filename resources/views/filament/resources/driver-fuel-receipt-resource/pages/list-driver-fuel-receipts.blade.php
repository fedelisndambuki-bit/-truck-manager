@php use App\Filament\Resources\DriverFuelReceiptResource; @endphp
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
        .receipt-thumb{width:40px;height:40px;border-radius:4px;object-fit:cover;cursor:pointer;border:1px solid #e5e7eb;transition:transform 0.1s}
        .receipt-thumb:hover{transform:scale(1.1)}
        .badge{display:inline-block;padding:5px 12px;border-radius:12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
        .badge.pending{background:#fef3c7;color:#92400e}
        .badge.approved{background:#d1fae5;color:#065f46}
        .badge.failed{background:#fee2e2;color:#991b1b}
        .actions{display:flex;gap:10px}
        .action-btn{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;transition:all 0.2s;border:none;cursor:pointer}
        .btn-edit{background:rgba(59,130,246,0.1);color:#3b82f6}
        .btn-edit:hover{background:#3b82f6;color:#fff}
        .btn-approve{background:rgba(16,185,129,0.1);color:#10b981}
        .btn-approve:hover{background:#10b981;color:#fff}
        .btn-decline{background:rgba(239,68,68,0.1);color:#ef4444}
        .btn-decline:hover{background:#ef4444;color:#fff}
        .btn-delete{background:rgba(107,114,128,0.1);color:#6b7280}
        .btn-delete:hover{background:#6b7280;color:#fff}
    </style>

    <div class="page-header-custom">
        <div class="page-header-content">
            <div class="page-header-icon">
                <x-heroicon-o-receipt-percent style="width:20px;height:20px;" />
            </div>
            <div>
                <h1>Fuel Receipt Reviews</h1>
                <p>Review and verify driver fuel receipt submissions</p>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">
                <x-heroicon-o-clipboard-document-list style="width:16px;height:16px;" />
                Receipt List
            </div>
            <div class="table-results">
                @php
                    $records = $this->getTable()->getRecords();
                    $firstItem = $records->firstItem();
                    $lastItem = $records->lastItem();
                    $total = $records->total();
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
                    <th>Receipt</th>
                    <th>Driver / Trip</th>
                    <th>Vendor</th>
                    <th>Litres</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Captured</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $receipt)
                @php $status = strtolower($receipt->status ?? 'pending'); @endphp
                <tr wire:key="receipt-{{ $receipt->id }}">
                    <td>
                        @if($receipt->receipt_path)
                            <img src="{{ asset('storage/'.$receipt->receipt_path) }}" class="receipt-thumb" onclick="window.open(this.src)" />
                        @else
                            <span class="text-gray-400">No Image</span>
                        @endif
                    </td>
                    <td>
                        <div class="font-medium">{{ optional($receipt->driver)->user->name ?? 'Unknown' }}</div>
                        <div class="text-xs text-gray-500">Trip #{{ $receipt->trip_id }}</div>
                    </td>
                    <td>{{ $receipt->vendor }}</td>
                    <td>{{ $receipt->litres }} L</td>
                    <td class="font-medium">KES {{ number_format((float)($receipt->amount ?? 0), 2) }}</td>
                    <td><span class="badge {{ $status }}">{{ strtoupper($receipt->status ?? 'PENDING') }}</span></td>
                    <td class="text-xs">{{ optional($receipt->captured_at)->format('M d, Y H:i') }}</td>
                    <td>
                        <div class="actions">
                            <button type="button" class="action-btn btn-edit" title="Edit" wire:click="mountTableAction('edit', '{{ $receipt->id }}')">
                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                            </button>

                            @if($receipt->status !== 'approved')
                                <button type="button" class="action-btn btn-approve" title="Approve" wire:click="mountTableAction('approve', '{{ $receipt->id }}')">
                                    <x-heroicon-m-check-circle class="w-4 h-4" />
                                </button>
                            @endif

                            @if($receipt->status !== 'failed')
                                <button type="button" class="action-btn btn-decline" title="Decline" wire:click="mountTableAction('fail', '{{ $receipt->id }}')">
                                    <x-heroicon-m-x-circle class="w-4 h-4" />
                                </button>
                            @endif

                            <button type="button" class="action-btn btn-delete" title="Delete" wire:click="mountTableAction('delete', '{{ $receipt->id }}')">
                                <x-heroicon-m-trash class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">No fuel receipts found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($records->hasPages())
        <div style="padding:20px 25px;border-top:1px solid #f3f4f6;">
            {{ $records->links() }}
        </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
