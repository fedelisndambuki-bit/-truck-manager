<x-filament-panels::page>
    <x-slot name="heading">
        <div class="page-header-custom" style="background-color: #2f4f4f; padding: 25px 35px; color: #fff; border-radius: 10px; display: block; width: 100%; text-align: left;">
            <div class="page-header-content" style="display: flex; align-items: center; gap: 12px;">
                <div class="page-header-icon" style="background: rgba(255, 255, 255, 0.15); padding: 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <h1 style="font-size: 24px; font-weight: 600; margin-bottom: 4px; color: #fff; line-height: 1.2; text-transform: none;">Plans</h1>
                    <p style="font-size: 13px; opacity: 0.85; font-weight: 400; color: #fff; margin: 0; text-transform: none;">Configure subscription plans, pricing and module access</p>
                </div>
            </div>
        </div>
    </x-slot>

    <style>
        .plan-custom-view {
            --teal-dark:  #1e3a38;
            --teal-mid:   #2a4f4c;
            --teal-light: #3d706b;
            --teal-glow:  #4e9e97;
            --bg:         #f5f6f8;
            --card:       #ffffff;
            --border:     #e4e7ec;
            --text-1:     #111827;
            --text-2:     #4b5563;
            --text-3:     #9ca3af;
            --green:      #10b981;
            --green-bg:   #ecfdf5;
            --red:        #ef4444;
            --red-bg:     #fef2f2;
            --amber:      #f59e0b;
            --amber-bg:   #fffbeb;
            --blue:       #3b82f6;
            --blue-bg:    #eff6ff;
            font-family: 'Inter', sans-serif;
            color: var(--text-1);
        }

        .plan-custom-view main { max-width: 100%; padding: 0; }

        .table-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0,0,0,0.04);
            animation: fadeUp 0.4s ease both;
        }

        .custom-table { width: 100%; border-collapse: collapse; }

        .custom-table thead tr {
            background: #2f4f4f; /* DarkSlateGrey */
            border-bottom: 1px solid var(--border);
        }
        .custom-table thead th {
            padding: 16px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #ffffff; /* White Font */
            text-transform: uppercase;
            letter-spacing: 0.1em;
            white-space: nowrap;
        }

        .custom-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.12s;
        }
        .custom-table tbody tr:last-child { border-bottom: none; }
        .custom-table tbody tr:hover { background: #f8f9fb; }

        .custom-table td { padding: 16px 20px; font-size: 13px; color: var(--text-1); vertical-align: middle; }

        .plan-name { font-weight: 600; color: #3b82f6; cursor: pointer; }
        .plan-name:hover { text-decoration: underline; }

        .limits-pill {
            display: inline-block; background: #f3f4f6; color: #4b5563;
            padding: 4px 10px; border-radius: 8px; font-size: 11px; font-family: 'DM Mono', monospace;
        }

        .price-tag { font-weight: 700; color: var(--text-1); font-size: 14px; }

        .act-btn {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
            background: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: #3b82f6; font-size: 14px; transition: all 0.2s;
        }
        .act-btn:hover { background: #eff6ff; transform: translateY(-1px); }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; gap: 12px;
        }
        .search-box {
            display: flex; align-items: center; gap: 9px;
            background: var(--card); border: 1px solid var(--border);
            border-radius: 9px; padding: 8px 13px;
        }
        .search-box input {
            border: none; background: none; font-size: 13px; width: 220px;
        }
        .search-box input:focus { outline: none; }
    </style>

    <div class="plan-custom-view">
        <main>
            <!-- Toolbar -->
            <div class="toolbar">
                <div class="search-box">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="tableSearch" placeholder="Search plans…" />
                </div>
            </div>

            <!-- Table -->
            <div class="table-card">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Plan Name</th>
                            <th>Price</th>
                            <th>Limits</th>
                            <th>Features</th>
                            <th>Created</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->getTable()->getRecords() as $record)
                        <tr>
                            <td>
                                <div class="plan-name">
                                    {{ $record->name }}
                                </div>
                            </td>
                            <td>
                                <div class="price-tag">
                                    {{ number_format($record->price, 2) }} <span style="font-size: 10px; opacity: 0.6;">KES</span>
                                </div>
                            </td>
                            <td>
                                <div class="limits-pill">
                                    Truck: {{ $record->truck_limit }} | Driver: {{ $record->driver_limit }} | AI: {{ $record->ai_requests_limit }}
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 11px; color: var(--text-2);">
                                    @if(is_array($record->features) && count($record->features) > 0)
                                        {{ count($record->features) }} enabled features
                                    @else
                                        Standard features
                                    @endif
                                </div>
                            </td>
                            <td style="color: var(--text-2); font-family: 'DM Mono', monospace;">
                                {{ $record->created_at->format('M d, Y') }}
                            </td>
                            <td>
                                <a class="act-btn" title="Edit" href="{{ \App\Filament\SuperAdmin\Resources\PlanResource::getUrl('edit', ['record' => $record->id]) }}">
                                    ✎
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 60px; color: var(--text-3);">
                                No plans found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $this->getTable()->getRecords()->links() }}
            </div>
        </main>
    </div>
</x-filament-panels::page>
