@php use App\Filament\Resources\DriverPodResource; @endphp
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
        .pod-thumb{width:40px;height:40px;border-radius:4px;object-fit:cover;cursor:pointer;border:1px solid #e5e7eb;transition:transform 0.1s}
        .pod-thumb:hover{transform:scale(1.1)}
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
                <x-heroicon-o-clipboard-document-check style="width:20px;height:20px;" />
            </div>
            <div>
                <h1>POD Reviews</h1>
                <p>Review proof-of-delivery uploads from drivers</p>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">
                <x-heroicon-o-clipboard-document-list style="width:16px;height:16px;" />
                POD List
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
                    <th>Document</th>
                    <th>Driver</th>
                    <th>Trip Details</th>
                    <th>Receiver</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $pod)
                <tr wire:key="pod-{{ $pod->id }}">
                    <td>
                        @if($pod->image_path)
                            <img src="{{ asset('storage/'.$pod->image_path) }}" class="pod-thumb" wire:click="mountTableAction('image_preview', '{{ $pod->id }}')" />
                        @else
                            <span class="text-gray-400">No Document</span>
                        @endif
                    </td>
                    <td>
                        <div class="font-medium">{{ optional($pod->driver)->user->name ?? 'Unknown' }}</div>
                        <div class="text-xs text-gray-500">{{ $pod->driver?->license_number ?? 'No License' }}</div>
                    </td>
                    <td>
                        <div class="font-medium">Trip #{{ $pod->trip_id }}</div>
                        <div class="text-xs text-gray-400">{{ $pod->pickup_details }} → {{ $pod->dropoff_details }}</div>
                    </td>
                    <td>
                        <div class="font-medium">{{ $pod->receiver_name ?? 'N/A' }}</div>
                        <div class="text-xs text-gray-400">{{ optional($pod->delivered_at)->format('M d, H:i') }}</div>
                    </td>
                    <td><span class="badge {{ strtolower($pod->status ?? 'pending') }}">{{ strtoupper($pod->status ?? 'PENDING') }}</span></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="action-btn btn-edit" title="Edit" wire:click="mountTableAction('edit', '{{ $pod->id }}')">
                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                            </button>
                            
                            @if($pod->status !== 'approved')
                                <button type="button" class="action-btn btn-approve" title="Approve" wire:click="mountTableAction('approve', '{{ $pod->id }}')">
                                    <x-heroicon-m-check-circle class="w-4 h-4" />
                                </button>
                            @endif
                            
                            @if($pod->status !== 'failed')
                                <button type="button" class="action-btn btn-decline" title="Decline" wire:click="mountTableAction('fail', '{{ $pod->id }}')">
                                    <x-heroicon-m-x-circle class="w-4 h-4" />
                                </button>
                            @endif

                            <button type="button" class="action-btn btn-delete" title="Delete" wire:click="mountTableAction('delete', '{{ $pod->id }}')">
                                <x-heroicon-m-trash class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;">No records found</td>
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
