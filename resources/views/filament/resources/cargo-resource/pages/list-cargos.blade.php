@php use App\Filament\Resources\CargoResource; @endphp
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
        .cargo-id{color:#3b82f6;font-weight:600;display:flex;align-items:center;gap:6px;cursor:pointer}
        .cargo-id:hover{text-decoration:underline}
        .amount{font-weight:600;font-size:14px}
        .amount.positive{color:#10b981}
        .amount.negative{color:#ef4444}
        .actions{display:flex;gap:8px}
        .action-btn{width:32px;height:32px;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;font-size:14px}
        .action-btn.view{background:rgba(59,130,246,.1);color:#3b82f6}
        .action-btn.edit{background:rgba(16,185,129,.1);color:#10b981}
        .action-btn:hover{transform:translateY(-2px);box-shadow:0 2px 4px rgba(0,0,0,.1)}
    </style>

    <div class="page-header-custom">
        <div class="page-header-content">
            <div class="page-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-8.954-4.477a1.125 1.125 0 00-1.007 0L1.335 7.5m18.915 0L12 11.25M20.25 7.5v9a1.125 1.125 0 01-.621 1.01l-8.25 4.125a1.125 1.125 0 01-1.007 0l-8.25-4.125A1.125 1.125 0 011.5 16.5v-9m18.75 0l-9 4.5m-9-4.5L12 11.25" />
                </svg>
            </div>
            <div>
                <h1>Cargo</h1>
                <p>Manage client cargo items and details</p>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path d="M9.243 3.06a1 1 0 01.514 0l6.5 2a1 1 0 01.743.964V13a1 1 0 01-.629.928l-6.5 2.6a1 1 0 01-.743 0l-6.5-2.6A1 1 0 011 13V6.024a1 1 0 01.743-.964l6.5-2z"/></svg>
                Cargo List
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
                    <th>Description</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Weight</th>
                    <th>Value</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->getTable()->getRecords() as $cargo)
                @php
                    $value = (float) ($cargo->value ?? 0);
                @endphp
                <tr>
                    <td>
                        <div class="cargo-id">
                            <a href="{{ CargoResource::getUrl('edit', ['record' => $cargo->id]) }}">
                                {{ $cargo->description }}
                            </a>
                        </div>
                    </td>
                    <td>{{ optional($cargo->client)->company_name }}</td>
                    <td>{{ $cargo->cargo_type }}</td>
                    <td>{{ $cargo->weight }} Tons</td>
                    <td>
                        <span class="amount {{ $value >= 0 ? 'positive' : 'negative' }}">KES {{ number_format($value, 2) }}</span>
                    </td>
                    <td>
                        <div class="actions">
                            <a class="action-btn view" title="View" href="{{ CargoResource::getUrl('edit', ['record' => $cargo->id]) }}">👁</a>
                            <a class="action-btn edit" title="Edit" href="{{ CargoResource::getUrl('edit', ['record' => $cargo->id]) }}">✎</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;">No cargo found</td>
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
