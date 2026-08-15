@php use App\Filament\Resources\FuelLogResource; @endphp
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
        .actions{display:flex;gap:8px}
        .action-btn{width:32px;height:32px;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;font-size:14px}
        .action-btn.edit{background:rgba(16,185,129,.1);color:#10b981}
        .action-btn:hover{transform:translateY(-2px);box-shadow:0 2px 4px rgba(0,0,0,.1)}
    </style>

    <div class="page-header-custom">
        <div class="page-header-content">
            <div class="page-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3" />
                </svg>
            </div>
            <div>
                <h1>Fuel Logs</h1>
                <p>Monitor fuel usage, costs and mileage at refueling</p>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v12H4a1 1 0 01-1-1V4z"/></svg>
                Fuel Log List
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
                    <th>Date</th>
                    <th>Truck</th>
                    <th>Driver</th>
                    <th>Quantity</th>
                    <th>Total Cost</th>
                    <th>Station / Mileage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->getTable()->getRecords() as $log)
                <tr>
                    <td>{{ optional($log->date)->format('M d, Y') }}</td>
                    <td>{{ optional($log->truck)->plate_number }}</td>
                    <td>{{ optional($log->driver)->full_name }}</td>
                    <td>{{ $log->quantity_litres }} L</td>
                    <td>KES {{ number_format((float) ($log->total_cost ?? 0), 2) }}</td>
                    <td>{{ $log->fuel_station }} — {{ number_format((float) ($log->mileage_at_fueling ?? 0)) }} KM</td>
                    <td>
                        <div class="actions">
                            <a class="action-btn edit" title="Edit" href="{{ FuelLogResource::getUrl('edit', ['record' => $log->id]) }}">✎</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">No fuel logs found</td>
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
