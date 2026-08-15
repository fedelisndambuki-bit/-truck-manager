<x-filament-panels::page>
    @php
        $totalFlags = \App\Models\FeatureFlag::count();
        $enabledFlags = \App\Models\FeatureFlag::where('enabled', true)->count();
        $disabledFlags = \App\Models\FeatureFlag::where('enabled', false)->count();
        // Environments logic is illustrative since model doesn't store explicit environments array
        // We could count unique companies or plans as proxy, or just hardcode as per design for now
        $envs = ['prod', 'stage', 'dev']; 
    @endphp

    <style>
        /* Scoped styles to prevent global pollution */
        .feature-flags-custom-view {
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
            font-family: 'DM Sans', sans-serif;
            color: var(--text-1);
        }

        /* ── MAIN ── */
        .feature-flags-custom-view main { max-width: 100%; padding: 0; }

        /* Stats row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex; flex-direction: column; gap: 6px;
            animation: fadeUp 0.4s ease both;
        }
        .stat-card:nth-child(1) { animation-delay: 0ms; }
        .stat-card:nth-child(2) { animation-delay: 60ms; }
        .stat-card:nth-child(3) { animation-delay: 120ms; }
        .stat-card:nth-child(4) { animation-delay: 180ms; }

        .stat-label { font-size: 11.5px; color: var(--text-3); font-weight: 500; text-transform: uppercase; letter-spacing: 0.6px; }
        .stat-value { font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 700; color: var(--text-1); }
        .stat-pill {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px;
            width: fit-content;
        }

        /* Toolbar */
        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px; gap: 12px; flex-wrap: wrap;
        }
        .filter-tabs { display: flex; gap: 4px; }
        .tab {
            padding: 6px 14px; border-radius: 8px; font-size: 12.5px;
            font-weight: 500; cursor: pointer; border: none;
            background: none; color: var(--text-2);
            font-family: 'DM Sans', sans-serif;
            transition: background 0.15s, color 0.15s;
        }
        .tab:hover { background: #eef0f4; }
        .tab.active { background: var(--teal-dark); color: #fff; }

        .toolbar-right { display: flex; gap: 10px; align-items: center; }

        .search-box {
            display: flex; align-items: center; gap: 9px;
            background: var(--card); border: 1px solid var(--border);
            border-radius: 9px; padding: 8px 13px;
            transition: border-color 0.15s;
        }
        .search-box:focus-within { border-color: var(--teal-light); }
        .search-box input {
            border: none; background: none; font-size: 13px;
            color: var(--text-1); font-family: 'DM Sans', sans-serif;
            width: 180px; caret-color: var(--teal-glow);
        }
        .search-box input:focus { outline: none; }
        .search-box input::placeholder { color: var(--text-3); }

        /* Table card */
        .table-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0,0,0,0.04);
            animation: fadeUp 0.4s ease 0.2s both;
        }

        .custom-table { width: 100%; border-collapse: collapse; }

        .custom-table thead tr {
            background: #2f4f4f;
            border-bottom: 1px solid var(--border);
        }
        .custom-table thead th {
            padding: 12px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-family: 'DM Mono', monospace;
            white-space: nowrap;
        }

        .custom-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.12s, transform 0.12s;
            animation: fadeUp 0.35s ease both;
        }
        .custom-table tbody tr:last-child { border-bottom: none; }
        .custom-table tbody tr:hover { background: #f8f9fb; transform: translateX(2px); }

        .custom-table td { padding: 14px 20px; font-size: 13px; color: var(--text-1); vertical-align: middle; }

        .flag-name {
            font-weight: 600; color: var(--text-1);
            font-family: 'DM Mono', monospace; font-size: 12.5px;
            display: flex; align-items: center; gap: 8px;
        }
        .flag-desc { font-size: 11.5px; color: var(--text-3); margin-top: 2px; }

        /* Toggle */
        .toggle-wrap { display: flex; align-items: center; gap: 8px; }
        .toggle {
            position: relative; width: 36px; height: 20px; cursor: pointer;
        }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; inset: 0; border-radius: 20px;
            background: #d1d5db; transition: background 0.2s;
        }
        .slider::before {
            content: ""; position: absolute;
            width: 14px; height: 14px; border-radius: 50%;
            background: #fff; left: 3px; top: 3px;
            transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .toggle input:checked + .slider { background: var(--green); }
        .toggle input:checked + .slider::before { transform: translateX(16px); }
        .toggle-label { font-size: 12px; font-weight: 500; }
        .on  { color: var(--green); }
        .off { color: var(--text-3); }

        /* Env badges */
        .env-list { display: flex; gap: 6px; flex-wrap: wrap; }
        .env-badge {
            font-size: 10.5px; font-weight: 600; padding: 2px 8px;
            border-radius: 6px; font-family: 'DM Mono', monospace;
        }
        .env-prod  { background: #fef2f2; color: #dc2626; }
        .env-stage { background: #fffbeb; color: #d97706; }
        .env-dev   { background: #eff6ff; color: #2563eb; }
        .env-all   { background: #eff6ff; color: #2563eb; }

        /* Type badge */
        .type-badge {
            font-size: 10.5px; font-weight: 600; padding: 3px 9px;
            border-radius: 6px; text-transform: uppercase; letter-spacing: 0.4px;
        }
        .type-release  { background: #ecfdf5; color: #059669; }
        .type-experiment { background: #f5f3ff; color: #7c3aed; }
        .type-ops      { background: #fff7ed; color: #c2410c; }
        .type-permission { background: #f0f9ff; color: #0369a1; }
        .type-global     { background: #f0f9ff; color: #0369a1; } /* Fallback */

        /* Rollout bar */
        .rollout-wrap { display: flex; align-items: center; gap: 8px; min-width: 100px; }
        .rollout-bar {
            flex: 1; height: 5px; background: #e5e7eb;
            border-radius: 10px; overflow: hidden;
        }
        .rollout-fill { height: 100%; border-radius: 10px; background: var(--teal-glow); }
        .rollout-pct { font-size: 11px; color: var(--text-3); font-family: 'DM Mono', monospace; }

        /* Actions */
        .actions { display: flex; gap: 4px; }
        .act-btn {
            width: 28px; height: 28px; border-radius: 7px; border: 1px solid var(--border);
            background: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: var(--text-3); font-size: 13px; transition: background 0.12s, color 0.12s;
        }
        .act-btn:hover { background: #f3f4f6; color: var(--text-1); }
        .act-btn.danger:hover { background: #fef2f2; color: var(--red); border-color: #fca5a5; }

        /* Empty state */
        .empty-state {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; padding: 80px 0; gap: 14px;
        }
        .empty-icon {
            width: 64px; height: 64px; border-radius: 18px;
            background: linear-gradient(135deg, #f0f4f8, #e8ecf0);
            display: flex; align-items: center; justify-content: center; font-size: 28px;
        }
        .empty-title { font-size: 15px; font-weight: 600; color: var(--text-2); }
        .empty-sub { font-size: 13px; color: var(--text-3); }
        .empty-btn {
            margin-top: 4px;
            background: var(--teal-dark); color: #fff;
            border: none; border-radius: 9px;
            padding: 9px 20px; font-size: 13px; font-weight: 600;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            transition: opacity 0.15s;
        }
        .empty-btn:hover { opacity: 0.85; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 700px) {
            .stats-row { grid-template-columns: 1fr 1fr; }
            .custom-table thead th:nth-child(4), .custom-table td:nth-child(4),
            .custom-table thead th:nth-child(5), .custom-table td:nth-child(5) { display: none; }
        }
    </style>

    <div class="feature-flags-custom-view">
        <main>
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-label">Total Flags</span>
                    <span class="stat-value">{{ $totalFlags }}</span>
                    <span class="stat-pill" style="background:#f0f9ff;color:#0369a1;">↑ 2 this week</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Enabled</span>
                    <span class="stat-value" style="color:var(--green)">{{ $enabledFlags }}</span>
                    <span class="stat-pill" style="background:var(--green-bg);color:var(--green);">● Active</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Disabled</span>
                    <span class="stat-value" style="color:var(--text-3)">{{ $disabledFlags }}</span>
                    <span class="stat-pill" style="background:#f3f4f6;color:var(--text-3);">○ Inactive</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Environments</span>
                    <span class="stat-value">3</span>
                    <span class="stat-pill" style="background:#fff7ed;color:#c2410c;">prod · stage · dev</span>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="toolbar">
                <div class="filter-tabs">
                    <button class="tab active" onclick="setFilter('all', this)">All</button>
                    <button class="tab" onclick="setFilter('enabled', this)">Enabled</button>
                    <button class="tab" onclick="setFilter('disabled', this)">Disabled</button>
                    <button class="tab" onclick="setFilter('release', this)">Release</button>
                    <button class="tab" onclick="setFilter('experiment', this)">Experiment</button>
                </div>
                <div class="toolbar-right">
                    <div class="search-box">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="text" wire:model.live.debounce.300ms="tableSearch" placeholder="Search flags…" />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-card">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Flag Key</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Environments</th>
                            <th>Rollout</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->getTable()->getRecords() as $record)
                        <tr>
                            <td>
                                <div class="flag-name">
                                    <span style="color:var(--teal-glow)">⚑</span>
                                    {{ $record->key }}
                                </div>
                                <div class="flag-desc">
                                    {{ $record->description ?? 'No description provided' }}
                                </div>
                            </td>
                            <td>
                                <div class="toggle-wrap">
                                    <label class="toggle">
                                        <!-- Note: Wire up toggle if needed, or stick to static for now -->
                                        <input type="checkbox" {{ $record->enabled ? 'checked' : '' }} wire:click="updateTableRecord({{ $record->id }}, 'enabled', {{ !$record->enabled }})"/>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="toggle-label {{ $record->enabled ? 'on' : 'off' }}">
                                        {{ $record->enabled ? 'On' : 'Off' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @php
                                    // Derive type from relationships or fallback
                                    $type = 'global';
                                    if ($record->company_id) $type = 'permission'; // Example logic
                                    elseif ($record->plan_id) $type = 'release'; // Example logic
                                    elseif (str_contains($record->key, 'experiment')) $type = 'experiment';
                                    
                                    $class = match($type) {
                                        'release' => 'type-release',
                                        'experiment' => 'type-experiment',
                                        'ops' => 'type-ops',
                                        'permission' => 'type-permission',
                                        default => 'type-global',
                                    };
                                @endphp
                                <span class="type-badge {{ $class }}">{{ ucfirst($type) }}</span>
                            </td>
                            <td>
                                <div class="env-list">
                                    @if($record->company)
                                        <span class="env-badge env-prod">{{ $record->company->name }}</span>
                                    @elseif($record->plan)
                                        <span class="env-badge env-stage">{{ $record->plan->name }}</span>
                                    @else
                                        <span class="env-badge env-all">All Envs</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="rollout-wrap">
                                    <div class="rollout-bar">
                                        <div class="rollout-fill" style="width: {{ $record->rollout_percentage }}%"></div>
                                    </div>
                                    <span class="rollout-pct">{{ $record->rollout_percentage }}%</span>
                                </div>
                            </td>
                            <td style="color:var(--text-3);font-size:12px;font-family:'DM Mono',monospace">
                                {{ $record->updated_at->diffForHumans() }}
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="act-btn" title="Edit" href="{{ \App\Filament\SuperAdmin\Resources\FeatureFlagResource::getUrl('edit', ['record' => $record->id]) }}">✎</a>
                                    <!-- Delete action requires a form or livewire call -->
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon">🚩</div>
                                    <span class="empty-title">No feature flags found</span>
                                    <span class="empty-sub">Create your first flag to start controlling features</span>
                                    <a class="empty-btn" href="{{ \App\Filament\SuperAdmin\Resources\FeatureFlagResource::getUrl('create') }}">+ New feature flag</a>
                                </div>
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
