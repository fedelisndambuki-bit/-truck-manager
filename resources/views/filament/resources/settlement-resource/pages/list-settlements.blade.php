@php use App\Filament\Resources\SettlementResource; @endphp
@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
@endpush
<x-filament-panels::page>
    <style>
        /* Header matches sidebar: darkslategray #2f4f4f */
        .page-header-custom {
            background-color: #2f4f4f;
            padding: 25px 35px;
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .page-header-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header-icon {
            background: rgba(255, 255, 255, 0.15);
            padding: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-header-custom h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .page-header-custom p {
            font-size: 13px;
            opacity: 0.85;
            font-weight: 400;
        }

        /* Table Specific Styling */
        .table-section {
            background: white;
            border-radius: 10px; /* Match Filament section styling */
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-top: 20px; /* Space from filter widget */
        }

        .table-header-custom {
            background-color: #2f4f4f;
            color: #f9fafb;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px 10px 0 0;
        }

        .table-title {
            font-size: 15px;
            font-weight: 600;
            color: #f9fafb;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-results {
            font-size: 13px;
            color: rgba(249, 250, 251, 0.9);
        }

        .table-results strong {
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
        }

        thead th {
            padding: 14px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }

        /* KPI Cards Styling Fix */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .stat-subtext {
            font-size: 12px;
            color: #9ca3af;
        }

        /* Export Buttons Styling - Reverted to distinct colors */
        .btn-export {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
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

        tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.15s;
        }

        tbody tr:hover {
            background-color: #f9fafb;
        }

        tbody td {
            padding: 16px 20px;
            font-size: 13px;
            color: #374151;
        }

        .settlement-id {
            color: #3b82f6;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .settlement-id:hover {
            text-decoration: underline;
        }

        .amount {
            font-weight: 600;
            font-size: 14px;
        }

        .amount.positive {
            color: #10b981;
        }

        .amount.negative {
            color: #ef4444;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.paid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 14px;
        }

        .action-btn.view {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .action-btn.download {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="page-header-custom">
        <div class="page-header-content">
            <div class="page-header-icon">
                <i class="fas fa-receipt" style="font-size: 20px;"></i>
            </div>
            <div>
                <h1>Settlements & Payments</h1>
                <p>Track your payments, settlements, and transaction details</p>
            </div>
        </div>
    </div>



    <div class="filament-tables-container rounded-xl flex flex-col gap-6 mb-6"> {{-- Container for widgets --}}
        {{-- KPI Cards Widget --}}
        @livewire(\App\Filament\Resources\SettlementResource\Widgets\SettlementStatsWidget::class)

        {{-- Filter Widget --}}
        @livewire(\App\Filament\Resources\SettlementResource\Widgets\SettlementFilterWidget::class)
    </div>
    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">
                <i class="fas fa-list"></i>
                Settlement Checks
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
                    <th>Settlement ID</th>
                    <th>Settlement Date</th>
                    <th>Pay Period</th>
                    <th>Trucks</th>
                    <th>Trips</th>
                    <th>Net Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->getTable()->getRecords() as $settlement)
                <tr>
                    <td>
                        <div class="settlement-id">
                            <i class="fas fa-file-alt"></i>
                            <a href="{{ SettlementResource::getUrl('edit', ['record' => $settlement->id]) }}">
                                {{ $settlement->settlement_id }}
                            </a>
                        </div>
                    </td>
                    <td>{{ $settlement->settlement_date->format('M d, Y') }}</td>
                    <td>{{ $settlement->pay_period }}</td>
                    <td>{{ $settlement->trucks }}</td>
                    <td>{{ $settlement->trips }}</td>
                    <td>
                        <span class="amount {{ $settlement->net_amount >= 0 ? 'positive' : 'negative' }}">
                            ${{ number_format($settlement->net_amount, 2) }}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge {{ strtolower($settlement->status) }}">
                            {{ $settlement->status }}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <button class="action-btn view" title="View Details" wire:click="viewSettlement('{{ $settlement->id }}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn download" title="Download" wire:click="downloadSettlement('{{ $settlement->id }}')">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="no-results-cell">
                        <i class="fas fa-receipt"></i>
                        <div class="no-results-title">No results</div>
                        <div class="no-results-msg">No settlements found matching your criteria</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($this->getTable()->getRecords()->hasPages())
        <div style="padding: 20px 25px; border-top: 1px solid #f3f4f6;">
            {{ $this->getTable()->getRecords()->links() }}
        </div>
        @endif
    </div>

    <script>
        // These will be Livewire methods, so no need for direct JS alerts.
        // The wire:click methods will handle the actions.
    </script>
</x-filament-panels::page>
