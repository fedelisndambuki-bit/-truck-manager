@php use App\Filament\Resources\AiLogResource; @endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    <style>
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

        .table-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-top: 20px;
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
            vertical-align: top;
        }

        .log-id {
            color: #3b82f6;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .log-id:hover {
            text-decoration: underline;
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

        .status-badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-view {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .btn-view:hover {
            background: #3b82f6;
            color: white;
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }
    </style>

    <div class="page-header-custom">
        <div class="page-header-content">
            <div class="page-header-icon">
                <x-heroicon-o-cpu-chip class="w-5 h-5" />
            </div>
            <div>
                <h1>AI Logs</h1>
                <p>Review assistant usage, response times, and failures</p>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">
                <x-heroicon-o-list-bullet class="w-4 h-4" />
                AI Requests
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
                    <th>Log ID</th>
                    <th>User</th>
                    <th>Question</th>
                    <th>Status</th>
                    <th>Response (ms)</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $log)
                <tr wire:key="ailog-{{ $log->id }}">
                    <td>
                        <div class="log-id" wire:click="mountTableAction('view', '{{ $log->id }}')">
                            #{{ $log->id }}
                        </div>
                    </td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($log->question, 80) }}</td>
                    <td>
                        <span class="status-badge {{ strtolower($log->status) }}">
                            {{ $log->status }}
                        </span>
                    </td>
                    <td>{{ $log->response_time_ms ?? '—' }}</td>
                    <td>{{ $log->created_at?->format('M d, Y H:i') ?? '—' }}</td>
                    <td>
                        <div class="actions">
                            <button type="button" class="action-btn btn-view" title="View Details" wire:click="mountTableAction('view', '{{ $log->id }}')">
                                <x-heroicon-m-eye class="w-4 h-4" />
                            </button>
                            <button type="button" class="action-btn btn-delete" title="Delete" wire:click="mountTableAction('delete', '{{ $log->id }}')">
                                <x-heroicon-m-trash class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #9ca3af;">
                        No logs found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($records->hasPages())
        <div style="padding: 20px 25px; border-top: 1px solid #f3f4f6;">
            {{ $records->links() }}
        </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
