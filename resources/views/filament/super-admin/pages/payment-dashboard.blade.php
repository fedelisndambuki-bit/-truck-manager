<x-filament-panels::page>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@700;800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap');

        :root {
            --bg: #0d1515;
            --bg2: #111c1c;
            --bg3: #162020;
            --surface: #1c2a2a;
            --surface2: #223232;
            --border: #2a3d3d;
            --border2: #335050;
            --white: #f0f7f7;
            --white2: #c8dede;
            --slate: #2F4F4F;
            --slate-mid: #3d6464;
            --accent: #4ec9a0;
            --accent2: #3aa882;
            --accent-glow: rgba(78,201,160,0.15);
            --yellow: #e8c56a;
            --red: #e07070;
            --blue: #6aace8;
            --muted: #6a9090;
            --muted2: #4a7070;
        }

        .payment-dashboard-container {
            font-family: 'Instrument Sans', sans-serif;
            background: var(--bg);
            color: var(--white);
            margin: -24px;
            padding: 32px;
            min-height: 100vh;
        }

        .syne-font { font-family: 'Syne', sans-serif; }
        .mono-font { font-family: 'DM Mono', monospace; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 22px; position: relative; overflow: hidden; transition: border-color 0.2s, transform 0.2s; }
        .stat-card:hover { border-color: var(--border2); transform: translateY(-2px); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--accent); opacity: 0; transition: opacity 0.2s; }
        .stat-card.accent-card::before { opacity: 1; }
        .stat-label { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
        .stat-icon { width: 28px; height: 28px; background: var(--surface2); border-radius: 7px; display: flex; align-items: center; justify-content: center; }
        .stat-icon svg { width: 14px; height: 14px; fill: var(--muted); }
        .stat-icon.green svg { fill: var(--accent); }
        .stat-icon.blue svg { fill: var(--blue); }
        .stat-icon.yellow svg { fill: var(--yellow); }
        .stat-icon.red svg { fill: var(--red); }
        .stat-value { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; color: var(--white); letter-spacing: -1px; line-height: 1; margin-bottom: 8px; }
        .stat-change { font-size: 12px; font-family: 'DM Mono', monospace; display: flex; align-items: center; gap: 4px; }
        .stat-change.up { color: var(--accent); }
        .stat-change.down { color: var(--red); }

        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 28px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-bottom: 28px; }
        .card-header { padding: 18px 22px 14px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .card-title { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: var(--white); }
        .card-subtitle { font-size: 12px; color: var(--muted); font-family: 'DM Mono', monospace; }
        .card-body { padding: 20px 22px; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th { background: var(--bg3); padding: 10px 16px; text-align: left; font-family: 'DM Mono', monospace; font-size: 10px; color: var(--muted); text-transform: uppercase; white-space: nowrap; }
        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.1s; }
        tbody tr:hover { background: var(--bg3); }
        td { padding: 13px 16px; color: var(--white2); white-space: nowrap; }
        td.mono { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--muted); }
        td.amount { font-family: 'DM Mono', monospace; font-size: 13px; font-weight: 600; color: var(--white); }

        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-family: 'DM Mono', monospace; }
        .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
        .badge-completed { background: rgba(78,201,160,0.12); color: var(--accent); }
        .badge-pending   { background: rgba(232,197,106,0.12); color: var(--yellow); }
        .badge-failed    { background: rgba(224,112,112,0.12); color: var(--red); }

        .ledger-grid { display: grid; grid-template-columns: 1fr 2fr 1fr 1fr; font-size: 13px; }
        .ledger-head { display: contents; }
        .ledger-head > div { background: var(--bg3); padding: 10px 14px; font-family: 'DM Mono', monospace; font-size: 10px; color: var(--muted); text-transform: uppercase; border-bottom: 1px solid var(--border); }
        .ledger-row-grid { display: contents; }
        .ledger-row-grid > div { padding: 11px 14px; border-bottom: 1px solid var(--border); display: flex; align-items: center; }
        .dr { color: var(--red); font-family: 'DM Mono', monospace; font-weight: 600; }
        .cr { color: var(--accent); font-family: 'DM Mono', monospace; font-weight: 600; }

        .rev-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .rev-label { font-size: 11px; color: var(--muted); width: 100px; font-family: 'DM Mono', monospace; }
        .rev-bar-wrap { flex: 1; height: 8px; background: var(--surface2); border-radius: 4px; overflow: hidden; }
        .rev-bar { height: 100%; border-radius: 4px; transition: width 1s; }
        .rev-val { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--white2); width: 100px; text-align: right; }

        .section-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }

        .divider { height: 1px; background: var(--border); margin: 20px 0; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .anim { animation: fadeInUp 0.4s ease both; }
    </style>

    <div class="payment-dashboard-container">
        
        <div class="stats-grid">
            <div class="stat-card accent-card anim" style="animation-delay: 0.05s;">
                <div class="stat-label">Today's Revenue <div class="stat-icon green"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15"/></svg></div></div>
                <div class="stat-value">KES {{ $stats['revenue'] }}</div>
                <div class="stat-change up">↑ Real-time system data</div>
            </div>
            <div class="stat-card anim" style="animation-delay: 0.1s;">
                <div class="stat-label">Transactions <div class="stat-icon blue"><svg viewBox="0 0 24 24"><path d="M19 3H5v14h14V3z"/></svg></div></div>
                <div class="stat-value">{{ $stats['totalTransactions'] }}</div>
                <div class="stat-change up">↑ Total processed</div>
            </div>
            <div class="stat-card anim" style="animation-delay: 0.15s;">
                <div class="stat-label">Pending <div class="stat-icon yellow"><svg viewBox="0 0 24 24"><path d="M12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/></svg></div></div>
                <div class="stat-value">{{ $stats['pendingCount'] }}</div>
                <div class="stat-change">Awaiting confirmation</div>
            </div>
            <div class="stat-card anim" style="animation-delay: 0.2s;">
                <div class="stat-label">Failed <div class="stat-icon red"><svg viewBox="0 0 24 24"><path d="M12 2z"/></svg></div></div>
                <div class="stat-value">{{ $stats['failedCount'] }}</div>
                <div class="stat-change down">↓ Cancelled or error</div>
            </div>
        </div>

        <div class="charts-row anim" style="animation-delay: 0.25s;">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Transaction Volume (14 days)</div>
                        <div class="card-subtitle">KES gross daily totals</div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 160px; display: flex; align-items: flex-end; gap: 8px;">
                        @php $max = count($reports['dailyVolume']) > 0 ? max($reports['dailyVolume']) : 1; @endphp
                        @foreach(range(13, 0) as $i)
                            @php 
                                $date = now()->subDays($i)->format('Y-m-d');
                                $val = $reports['dailyVolume'][$date] ?? 0;
                                $h = ($val / $max) * 100;
                            @endphp
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px;">
                                <div style="width: 100%; height: {{ max($h, 2) }}%; background: {{ $i == 0 ? 'var(--accent)' : 'var(--border2)' }}; border-radius: 4px;"></div>
                                <span style="font-size: 8px; color: var(--muted); font-family: 'DM Mono'; text-transform: uppercase;">{{ now()->subDays($i)->format('D') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><div class="card-title">Success Rate</div></div>
                <div class="card-body" style="text-align: center;">
                    <svg width="160" height="160" viewBox="0 0 160 160">
                        <circle cx="80" cy="80" r="60" fill="none" stroke="#223232" stroke-width="22"/>
                        <circle cx="80" cy="80" r="60" fill="none" stroke="#4ec9a0" stroke-width="22" stroke-dasharray="{{ ($stats['completedPercent']/100)*377 }} 377" stroke-linecap="round"/>
                    </svg>
                    <div style="margin-top:10px; font-family:Syne; font-weight:800; font-size:24px;">{{ $stats['completedPercent'] }}%</div>
                    <div style="font-size:10px; color:var(--muted); font-family:DM Mono;">COMPLETED</div>
                </div>
            </div>
        </div>

        <div class="card anim" style="animation-delay: 0.3s;">
            <div class="card-header">
                <div><div class="card-title">Recent Transactions</div><div class="card-subtitle">Last entries from system</div></div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                        @forelse($transactions->take(10) as $txn)
                            <tr>
                                <td class="mono">{{ $txn->transaction_id }}</td>
                                <td class="amount">KES {{ number_format($txn->amount, 2) }}</td>
                                <td>{{ strtoupper($txn->payment_method) }}</td>
                                <td class="mono">{{ Str::limit($txn->gateway_reference, 12) }}</td>
                                <td><span class="badge badge-{{ $txn->status }}">{{ $txn->status }}</span></td>
                                <td class="mono">{{ $txn->created_at->format('H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center; padding: 40px; color: var(--muted);">No transactions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card anim">
            <div class="card-header">
                <div><div class="card-title">Settlement Batches</div><div class="card-subtitle">Daily financial processing</div></div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Settlement ID</th><th>Date</th><th>Gross</th><th>Fees</th><th>Net Payout</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($settlements['items'] as $set)
                            <tr>
                                <td class="mono">{{ $set->settlement_id }}</td>
                                <td class="mono">{{ $set->created_at->format('d M Y') }}</td>
                                <td class="mono">KES {{ number_format($set->gross_amount, 2) }}</td>
                                <td class="mono" style="color: var(--red);">-{{ number_format($set->fees_total, 2) }}</td>
                                <td class="amount">KES {{ number_format($set->net_amount, 2) }}</td>
                                <td><span class="badge badge-completed">{{ $set->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center; padding: 40px; color: var(--muted);">No settlements found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card anim">
            <div class="card-header">
                <div><div class="card-title">Double-Entry Ledger</div><div class="card-subtitle">Live system journal</div></div>
            </div>
            <div class="ledger-grid">
                <div class="ledger-head">
                    <div>Account</div><div>Description</div><div>Debit</div><div>Credit</div>
                </div>
                @forelse($ledger as $entry)
                    <div class="ledger-row-grid">
                        <div style="font-family:'DM Mono',monospace;font-size:12px;color:var(--white2);">{{ $entry->account_type }}</div>
                        <div style="color:var(--muted);font-size:13px;">{{ $entry->description }}</div>
                        <div class="dr">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '—' }}</div>
                        <div class="cr">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '—' }}</div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; text-align:center; padding: 40px; color: var(--muted); border-bottom: 1px solid var(--border);">No ledger entries found.</div>
                @endforelse
            </div>
        </div>

        <div class="section-grid anim">
            <div class="card">
                <div class="card-header"><div><div class="card-title">Revenue Breakdown</div><div class="card-subtitle">Month-to-date</div></div></div>
                <div class="card-body">
                    <div style="margin-bottom:20px;">
                        <div style="font-size:12px;color:var(--muted);font-family:'DM Mono',monospace;">GROSS RECEIPTS</div>
                        <div style="font-family:'Syne',sans-serif;font-size:26px;font-weight:800;">KES {{ $reports['gross'] }}</div>
                    </div>
                    <div class="divider"></div>
                    <div class="rev-row">
                        <div class="rev-label">Gateway Fees</div>
                        <div class="rev-bar-wrap"><div class="rev-bar" style="width: 60%; background: var(--yellow);"></div></div>
                        <div class="rev-val" style="color: var(--red);">− {{ $reports['gateway'] }}</div>
                    </div>
                    <div class="rev-row">
                        <div class="rev-label">Platform Rev</div>
                        <div class="rev-bar-wrap"><div class="rev-bar" style="width: 25%; background: var(--accent);"></div></div>
                        <div class="rev-val" style="color: var(--accent);">+ {{ $reports['platform'] }}</div>
                    </div>
                    <div class="divider"></div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:13px;color:var(--muted);">NET PAYOUT</span>
                        <span style="font-family:'DM Mono',monospace;font-size:16px;font-weight:700;color:var(--accent);">KES {{ $reports['net'] }}</span>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><div class="card-title">Account Balances</div></div>
                <div class="card-body">
                    <div class="rev-row">
                        <div class="rev-label">cash</div>
                        <div class="rev-bar-wrap"><div class="rev-bar" style="width:100%;background:var(--accent);"></div></div>
                        <div class="rev-val">KES {{ $reports['balances']['cash'] }}</div>
                    </div>
                    <div class="rev-row">
                        <div class="rev-label">platform_rev</div>
                        <div class="rev-bar-wrap"><div class="rev-bar" style="width:30%;background:var(--blue);"></div></div>
                        <div class="rev-val">KES {{ $reports['balances']['platform'] }}</div>
                    </div>
                    <div class="rev-row">
                        <div class="rev-label">gateway_fees</div>
                        <div class="rev-bar-wrap"><div class="rev-bar" style="width:55%;background:var(--yellow);"></div></div>
                        <div class="rev-val">KES {{ $reports['balances']['gateway'] }}</div>
                    </div>
                    <div class="rev-row">
                        <div class="rev-label">liability</div>
                        <div class="rev-bar-wrap"><div class="rev-bar" style="width:15%;background:var(--red);"></div></div>
                        <div class="rev-val">KES {{ $reports['balances']['liability'] }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
