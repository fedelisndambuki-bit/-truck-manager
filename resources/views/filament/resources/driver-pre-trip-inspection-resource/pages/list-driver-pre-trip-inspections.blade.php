@php use App\Filament\Resources\DriverPreTripInspectionResource; @endphp
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
        .check-icon{width:18px;height:18px}
        .check-icon.ok{color:#10b981}
        .check-icon.fail{color:#ef4444}
        .actions{display:flex;gap:10px}
        .action-btn{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;transition:all 0.2s;border:none;cursor:pointer}
        .btn-edit{background:rgba(59,130,246,0.1);color:#3b82f6}
        .btn-edit:hover{background:#3b82f6;color:#fff}
        .btn-delete{background:rgba(239,68,68,0.1);color:#ef4444}
        .btn-delete:hover{background:#ef4444;color:#fff}
    </style>

    <div class="page-header-custom">
        <div class="page-header-content">
            <div class="page-header-icon">
                <x-heroicon-o-clipboard-document-check style="width:20px;height:20px;" />
            </div>
            <div>
                <h1>Pre-Trip Inspections</h1>
                <p>Track daily pre-trip safety checklist submissions</p>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">
                <x-heroicon-o-clipboard-document-list style="width:16px;height:16px;" />
                Inspection List
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
                    <th>Driver</th>
                    <th>Trip</th>
                    <th>Brakes</th>
                    <th>Lights</th>
                    <th>Tires</th>
                    <th>Load</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $inspection)
                <tr wire:key="inspection-{{ $inspection->id }}">
                    <td>{{ optional($inspection->driver)->user->name ?? 'Unknown' }}</td>
                    <td>Trip #{{ $inspection->trip_id }}</td>
                    <td>
                        @if($inspection->brakes)
                            <x-heroicon-m-check-circle class="check-icon ok" />
                        @else
                            <x-heroicon-m-x-circle class="check-icon fail" />
                        @endif
                    </td>
                    <td>
                        @if($inspection->lights)
                            <x-heroicon-m-check-circle class="check-icon ok" />
                        @else
                            <x-heroicon-m-x-circle class="check-icon fail" />
                        @endif
                    </td>
                    <td>
                        @if($inspection->tires)
                            <x-heroicon-m-check-circle class="check-icon ok" />
                        @else
                            <x-heroicon-m-x-circle class="check-icon fail" />
                        @endif
                    </td>
                    <td>
                        @if($inspection->load_secured)
                            <x-heroicon-m-check-circle class="check-icon ok" />
                        @else
                            <x-heroicon-m-x-circle class="check-icon fail" />
                        @endif
                    </td>
                    <td class="text-xs">{{ optional($inspection->submitted_at)->format('M d, Y H:i') }}</td>
                    <td>
                        <div class="actions">
                            <button type="button" class="action-btn btn-edit" title="Edit" wire:click="mountTableAction('edit', '{{ $inspection->id }}')">
                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                            </button>
                            <button type="button" class="action-btn btn-delete" title="Delete" wire:click="mountTableAction('delete', '{{ $inspection->id }}')">
                                <x-heroicon-m-trash class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">No inspections found</td>
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
