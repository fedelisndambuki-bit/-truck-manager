<x-filament-panels::page>
    <div x-data="{
        activeTab: 'dashboard',
        simVehicles: {{ $activeVehicles }},
        fixedCost: {{ $fixedMonthlyCost }},
        varCost: {{ $variableCostPerVehicle }},
        revPerVeh: {{ $revenuePerVehicle }},
        get contributionMargin() { return this.revPerVeh - this.varCost },
        get breakEvenCount() { return Math.ceil(this.fixedCost / Math.max(1, this.contributionMargin)) },
        get projectedProfit() { return (this.simVehicles * this.contributionMargin) - this.fixedCost },
        get projectedMargin() { 
            let rev = this.simVehicles * this.revPerVeh;
            return rev > 0 ? ((this.projectedProfit / rev) * 100).toFixed(1) : 0;
        }
    }">

        <!-- Financial Styles -->
        <style>
            .fin-card { background: white; border-radius: 1rem; border: 1px solid #f3f4f6; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); padding: 1.5rem; transition: transform 0.2s; }
            .fin-card.kpi-small { padding: 1rem; border-radius: 0.75rem; }
            .fin-card:hover { transform: translateY(-2px); }
            .fin-kpi-val { font-size: 1.75rem; font-weight: 800; color: #111827; line-height: 1; }
            .kpi-small .fin-kpi-val { font-size: 1.25rem; }
            .fin-kpi-label { font-size: 0.7rem; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
            .kpi-small .fin-kpi-label { font-size: 0.65rem; margin-bottom: 0.25rem; }
            .fin-tab-btn { padding: 0.6rem 1.2rem; border-radius: 0.75rem; font-size: 0.85rem; font-weight: 600; transition: all 0.2s; border: 1px solid transparent; }
            .fin-tab-btn.active { background: #2f4f4f; color: white; box-shadow: 0 4px 12px rgba(47, 79, 79, 0.2); }
            .fin-tab-btn:not(.active) { color: #4b5563; background: #fff; border-color: #e5e7eb; }
            .fin-tab-btn:not(.active):hover { border-color: #2f4f4f; color: #2f4f4f; }
            
            .health-meter-container { position: relative; width: 140px; height: 140px; }
            .health-meter-svg { transform: rotate(-90deg); }
            .health-meter-bg { fill: none; stroke: #f3f4f6; stroke-width: 10; }
            .health-meter-fill { fill: none; stroke: #10b981; stroke-width: 10; stroke-linecap: round; transition: stroke-dashoffset 0.8s ease-out; }
            .alert-pill { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; border-radius: 0.75rem; font-size: 0.85rem; font-weight: 600; }

            /* Board Reporting Specific Styles */
            .section-label { font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; color: #64748b; margin-bottom: 14px; display: flex; align-items: center; gap: 10px; margin-top: 32px; }
            .section-label::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
            .kpi-accent-bar { position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 12px 12px 0 0; }
            .kpi-delta { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; padding: 2px 8px; border-radius: 10px; margin-bottom: 4px; }
            .kpi-delta.up { background: #ecfdf5; color: #059669; }
            .kpi-delta.down { background: #fef2f2; color: #dc2626; }
            .kpi-delta.neutral { background: #f1f5f9; color: #64748b; }
            .metric-row { display: flex; align-items: center; justify-content: space-between; padding: 11px 0; border-bottom: 1px solid #e2e8f0; }
            .metric-row:last-child { border-bottom: none; }
            .mr-badge { font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
            .badge-green { background: #ecfdf5; color: #059669; }
            .badge-red { background: #fef2f2; color: #dc2626; }
            .badge-amber { background: #fffbeb; color: #d97706; }
            .badge-blue { background: #eff6ff; color: #2563eb; }
            .badge-purple { background: #f5f3ff; color: #7c3aed; }
            .badge-gray { background: #f1f5f9; color: #64748b; }
            .compare-table { width: 100%; border-collapse: collapse; }
            .compare-table th { font-size: 9px; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; padding: 12px; text-align: right; border-bottom: 2px solid #cbd5e1; background: #f8fafc; }
            .compare-table th:first-child { text-align: left; }
            .compare-table td { font-size: 12px; padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 500; }
            .compare-table td:first-child { text-align: left; color: #64748b; }
            .risk-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
            .risk-item:last-child { border-bottom: none; }
            .risk-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
            .health-score-wide { display: flex; align-items: center; gap: 20px; padding: 20px; background: #0f2027; border-radius: 12px; margin-bottom: 28px; color: white; }
            .hs-bar { height: 4px; border-radius: 2px; flex: 1; background: rgba(255,255,255,0.15); }
            .hs-bar.filled { background: #0ea5e9; }
            .no-scrollbar::-webkit-scrollbar { display: none; }
            .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

            /* NEW DESIGN STYLES */
            :root{
                --bg:#f0f2f5;
                --white:#ffffff;
                --ink:#0d1b2a;
                --ink2:#1e3148;
                --muted:#64748b;
                --muted2:#94a3b8;
                --border:#e2e8f0;
                --border2:#cbd5e1;
                --surface:#f8fafc;

                --green:#059669;   --green-bg:#ecfdf5;  --green-bd:#a7f3d0;  --green-light:#d1fae5;
                --red:#dc2626;     --red-bg:#fef2f2;    --red-bd:#fecaca;    --red-light:#fee2e2;
                --amber:#d97706;   --amber-bg:#fffbeb;  --amber-bd:#fde68a;  --amber-light:#fef3c7;
                --blue:#2563eb;    --blue-bg:#eff6ff;   --blue-bd:#bfdbfe;
                --purple:#7c3aed;  --purple-bg:#f5f3ff; --purple-bd:#ddd6fe;
                --teal:#0891b2;    --teal-bg:#ecfeff;

                --sans:'DM Sans',system-ui,sans-serif;
                --mono:'DM Mono',monospace;
            }

            .header-report{
                background:var(--white);
                border-bottom:1px solid var(--border);
                padding:0 32px;
                height:60px;
                display:flex;align-items:center;justify-content:space-between;
                position:sticky;top:0;z-index:200;
                box-shadow:0 1px 3px rgba(0,0,0,0.06);
                margin-left: -2rem; margin-right: -2rem; margin-top: -2rem; margin-bottom: 2rem;
            }
            .h-left{display:flex;align-items:center;gap:20px;}
            .h-logo{
                display:flex;align-items:center;gap:10px;
                text-decoration:none;
            }
            .h-logo-mark{
                width:32px;height:32px;border-radius:8px;
                background:linear-gradient(135deg,#0891b2,#0ea5e9);
                display:flex;align-items:center;justify-content:center;
                font-size:15px;flex-shrink:0;
            }
            .h-brand{font-size:15px;font-weight:700;color:var(--ink);letter-spacing:-0.3px;}
            .h-divider{width:1px;height:24px;background:var(--border);}
            .h-meta{display:flex;flex-direction:column;}
            .h-title{font-size:13px;font-weight:600;color:var(--ink);}
            .h-sub{font-size:11px;color:var(--muted);}

            .h-right{display:flex;align-items:center;gap:10px;}
            .h-badge{
                font-size:11px;font-family:var(--mono);
                background:var(--surface);border:1px solid var(--border2);
                color:var(--muted);padding:5px 12px;border-radius:6px;
            }
            .btn-report{
                display:inline-flex;align-items:center;gap:6px;
                padding:8px 16px;border-radius:8px;font-family:var(--sans);
                font-size:12px;font-weight:600;cursor:pointer;border:none;
                transition:all 0.15s;letter-spacing:0.01em;
            }
            .btn-green-report{background:var(--green);color:#fff;}
            .btn-green-report:hover{background:#047857;}
            .btn-outline-report{background:var(--white);color:var(--muted);border:1px solid var(--border2);}
            .btn-outline-report:hover{border-color:var(--muted2);color:var(--ink);}

            .report-strip{
                display:flex;align-items:center;justify-content:space-between;
                margin-bottom:20px;
            }
            .rs-eyebrow{
                font-size:10px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;
                color:var(--teal);margin-bottom:4px;
            }
            .rs-title{font-size:20px;font-weight:700;color:var(--ink);letter-spacing:-0.4px;}
            .rs-desc{font-size:12px;color:var(--muted);margin-top:2px;max-width:520px;}
            .rs-right{display:flex;align-items:center;gap:8px;}
            .qtr-chip{
                padding:5px 12px;border-radius:6px;font-size:11px;font-weight:600;
                cursor:pointer;border:1px solid var(--border2);
                color:var(--muted);background:var(--white);transition:all 0.15s;
            }
            .qtr-chip.active{background:var(--ink);color:#fff;border-color:var(--ink);}
            .qtr-chip:hover:not(.active){border-color:var(--muted2);}
            .gen-date{font-size:11px;color:var(--muted2);margin-left:4px;}

            .health-banner{
                background:#2F4F4F;
                border-radius:14px;
                padding:20px 28px;
                display:flex;align-items:center;gap:28px;
                margin-bottom:20px;
                position:relative;overflow:hidden;
            }
            .health-banner::before{
                content:'';position:absolute;inset:0;
                background:radial-gradient(ellipse at 80% 50%, rgba(255,255,255,0.07) 0%, transparent 60%);
                pointer-events:none;
            }
            .hb-score{
                width:64px;height:64px;border-radius:14px;
                background:rgba(255,255,255,0.08);border:1.5px solid rgba(255,255,255,0.15);
                display:flex;flex-direction:column;align-items:center;justify-content:center;
                flex-shrink:0;
            }
            .hb-score-val{font-size:24px;font-weight:800;color:#fff;line-height:1;}
            .hb-score-lbl{font-size:8px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.08em;margin-top:2px;}
            .hb-center{flex:1;}
            .hb-title{font-size:15px;font-weight:700;color:#fff;margin-bottom:3px;}
            .hb-desc{font-size:11px;color:rgba(255,255,255,0.45);line-height:1.5;}
            .hb-bar-wrap{margin-top:12px;display:flex;align-items:center;gap:10px;}
            .hb-bar{flex:1;height:5px;background:rgba(255,255,255,0.1);border-radius:3px;overflow:hidden;}
            .hb-bar-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,#0891b2,#06b6d4);}
            .hb-bar-pct{font-size:10px;color:rgba(255,255,255,0.4);font-family:var(--mono);white-space:nowrap;}
            .hb-right{text-align:right;flex-shrink:0;}
            .hb-trend-lbl{font-size:10px;color:rgba(255,255,255,0.35);margin-bottom:3px;text-transform:uppercase;letter-spacing:0.08em;}
            .hb-trend-val{font-size:22px;font-weight:700;color:#6ee7b7;font-family:var(--mono);}
            .hb-trend-sub{font-size:11px;color:rgba(255,255,255,0.4);margin-top:1px;}
            .hb-status-chip{
                display:inline-flex;align-items:center;gap:5px;
                background:rgba(220,38,38,0.2);border:1px solid rgba(220,38,38,0.4);
                color:#fca5a5;font-size:11px;font-weight:600;
                padding:3px 10px;border-radius:20px;margin-top:2px;
            }

            .section-lbl-new{
                font-size:10px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;
                color:var(--muted);margin:24px 0 12px;
                display:flex;align-items:center;gap:10px;
            }
            .section-lbl-new::after{content:'';flex:1;height:1px;background:var(--border);}

            .kpi-strip-new{
                display:grid;grid-template-columns:repeat(5,1fr);
                gap:12px;margin-bottom:4px;
            }
            .kpi-new{
                background:var(--white);border:1px solid var(--border);
                border-radius:12px;padding:18px 18px 16px;
                display:flex;flex-direction:column;gap:2px;
                position:relative;overflow:hidden;
                transition:box-shadow 0.15s,transform 0.15s;
            }
            .kpi-new:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(0,0,0,0.07);}
            .kpi-top-new{height:3px;position:absolute;top:0;left:0;right:0;border-radius:12px 12px 0 0;}
            .kpi-icon-row-new{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;}
            .kpi-icon-new{
                width:32px;height:32px;border-radius:8px;
                display:flex;align-items:center;justify-content:center;font-size:14px;
            }
            .kpi-lbl-new{font-size:11px;font-weight:500;color:var(--muted);margin-bottom:4px;}
            .kpi-val-new{font-size:22px;font-weight:700;color:var(--ink);letter-spacing:-0.5px;line-height:1.1;}
            .kpi-sub-new{font-size:11px;margin-top:6px;}
            
            .tag-new{
                display:inline-flex;align-items:center;gap:3px;
                font-size:10px;font-weight:600;padding:2px 7px;border-radius:5px;
            }
            .tag-green-new{background:var(--green-bg);color:var(--green);}
            .tag-red-new{background:var(--red-bg);color:var(--red);}
            .tag-amber-new{background:var(--amber-bg);color:var(--amber);}
            .tag-blue-new{background:var(--blue-bg);color:var(--blue);}
            .tag-gray-new{background:#f1f5f9;color:var(--muted);}
            .tag-purple-new{background:var(--purple-bg);color:var(--purple);}

            .card-new{
                background:var(--white);border:1px solid var(--border);
                border-radius:12px;overflow:hidden;
            }
            .card-hdr-new{
                padding:14px 18px;border-bottom:1px solid var(--border);
                display:flex;align-items:center;justify-content:space-between;
            }
            .card-title-new{
                display:flex;align-items:center;gap:8px;
                font-size:13px;font-weight:600;color:var(--ink);
            }
            .card-title-new .ico-new{
                width:28px;height:28px;border-radius:7px;
                display:flex;align-items:center;justify-content:center;font-size:13px;
            }
            .card-meta-new{font-size:11px;color:var(--muted2);}
            .card-body-new{padding:18px;}

            .mrow-new{
                display:flex;align-items:center;justify-content:space-between;
                padding:10px 0;border-bottom:1px solid var(--border);
            }
            .mrow-new:last-child{border-bottom:none;}
            .mrow-lbl-new{font-size:12px;color:var(--muted);}
            .mrow-val-new{font-size:13px;font-weight:600;color:var(--ink);}
            .mrow-val-new.green{color:var(--green);}
            .mrow-val-new.red{color:var(--red);}
            .mrow-val-new.amber{color:var(--amber);}
            .mrow-val-new.muted{color:var(--muted2);font-weight:400;}

            .progress-wrap-new{margin-top:4px;}
            .progress-hdr-new{display:flex;justify-content:space-between;font-size:11px;color:var(--muted);margin-bottom:5px;}
            .progress-track-new{height:5px;background:var(--border);border-radius:3px;overflow:hidden;}
            .progress-fill-new{height:100%;border-radius:3px;transition:width 0.6s ease;}

            .spark-wrap-new{margin-top:14px;}
            .spark-lbl-new{font-size:10px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted2);margin-bottom:8px;}

            .arr-center-new{
                text-align:center;padding:20px 0 18px;
                border-bottom:1px solid var(--border);margin-bottom:4px;
            }
            .arr-lbl-new{font-size:10px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}
            .arr-val-new{font-size:32px;font-weight:700;color:var(--ink);letter-spacing:-1px;line-height:1;}

            .runway-center-new{text-align:center;padding:16px 0 20px;}
            .runway-num-new{font-size:56px;font-weight:800;color:var(--red);letter-spacing:-2px;line-height:1;}
            .runway-unit-new{font-size:13px;color:var(--muted);margin-top:2px;}
            .runway-alert-new{
                display:flex;align-items:center;justify-content:center;gap:7px;
                margin:14px 0 18px;padding:9px 16px;
                background:var(--amber-bg);border:1px solid var(--amber-bd);
                border-radius:8px;font-size:12px;font-weight:600;color:var(--amber);
            }

            .risk-item-new{
                display:flex;align-items:flex-start;gap:12px;
                padding:13px 0;border-bottom:1px solid var(--border);
            }
            .risk-item-new:last-child{border-bottom:none;padding-bottom:0;}
            .risk-dot-new{
                width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px;
            }
            .risk-title-new{font-size:12px;font-weight:600;color:var(--ink);margin-bottom:2px;}
            .risk-desc-new{font-size:11px;color:var(--muted);line-height:1.5;}
            .risk-sev-new{font-size:10px;font-weight:600;margin-top:3px;letter-spacing:0.04em;}

            .ctable-new{width:100%;border-collapse:collapse;}
            .ctable-new thead th{
                font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;
                color:var(--muted);padding:9px 14px;
                border-bottom:2px solid var(--border2);
                background:var(--surface);text-align:right;
            }
            .ctable-new thead th:first-child{text-align:left;}
            .ctable-new tbody td{
                font-size:12px;font-family:var(--mono);
                padding:10px 14px;border-bottom:1px solid var(--border);
                text-align:right;color:var(--ink);
            }
            .ctable-new tbody td:first-child{text-align:left;color:var(--muted);font-family:var(--sans);font-size:12px;}
            .ctable-new tbody tr:last-child td{border-bottom:none;}
            .ctable-new tbody tr:hover td{background:#fafbfc;}

            .exp-center-new{text-align:center;padding:20px 20px 16px;}
            .exp-circle-new{
                width:72px;height:72px;border-radius:50%;margin:0 auto 14px;
                display:flex;align-items:center;justify-content:center;font-size:26px;
                border:2.5px solid var(--amber-bd);background:var(--amber-bg);
            }
            .exp-label-new{font-size:16px;font-weight:700;color:var(--amber);margin-bottom:6px;}
            .exp-desc-new{font-size:11px;color:var(--muted);line-height:1.6;max-width:280px;margin:0 auto;}
            .checklist-row-new{
                display:flex;align-items:center;justify-content:space-between;
                font-size:11px;padding:6px 0;border-top:1px solid var(--border);
                color:var(--muted);
            }
            .checklist-row-new.pass{color:var(--green);}
            .checklist-row-new.fail{color:var(--red-bd);}

            .report-footer-new{
                display:flex;align-items:flex-start;justify-content:space-between;
                margin-top:36px;padding-top:20px;
                border-top:1px solid var(--border);
            }
            .footer-note-new{font-size:11px;color:var(--muted2);line-height:1.7;max-width:480px;font-style:italic;}
            .footer-sig-new{text-align:right;}
            .footer-brand-new{font-size:13px;font-weight:700;color:var(--ink);}
            .footer-loc-new{font-size:11px;color:var(--muted2);margin-top:2px;}

            .g2-new{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
            .g3-new{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
            .g53-new{display:grid;grid-template-columns:5fr 3fr;gap:14px;}
            .g32-new{display:grid;grid-template-columns:3fr 2fr;gap:14px;}

            .svg-ico{display:block;width:16px;height:16px;flex-shrink:0;}
            .svg-ico-sm{display:block;width:14px;height:14px;flex-shrink:0;}
            .svg-ico-lg{display:block;width:20px;height:20px;flex-shrink:0;}

            @keyframes fadeUp {
                from{opacity:0;transform:translateY(12px);}
                to{opacity:1;transform:translateY(0);}
            }
            .kpi-new{animation:fadeUp 0.3s ease both;}
            .kpi-new:nth-child(1){animation-delay:0.04s}
            .kpi-new:nth-child(2){animation-delay:0.08s}
            .kpi-new:nth-child(3){animation-delay:0.12s}
            .kpi-new:nth-child(4){animation-delay:0.16s}
            .kpi-new:nth-child(5){animation-delay:0.20s}
            .card-new,.health-banner{animation:fadeUp 0.35s ease both;}

            @media print {
                .header-report .h-right, .header-report .btn-report, .fin-tab-btn, .fi-sidebar, .fi-topbar { display: none !important; }
                body { background: #fff !important; }
                .card-new, .kpi-new { box-shadow: none !important; break-inside: avoid; }
                .health-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .page-report { padding: 0; }
            }
        </style>

        <!-- SVG ICON SPRITE -->
        <svg xmlns="http://www.w3.org/2000/svg" style="display:none;">
            <symbol id="ic-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
            </symbol>
            <symbol id="ic-truck" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </symbol>
            <symbol id="ic-currency" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </symbol>
            <symbol id="ic-trending-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/>
            </symbol>
            <symbol id="ic-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </symbol>
            <symbol id="ic-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </symbol>
            <symbol id="ic-trending-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
            </symbol>
            <symbol id="ic-scale" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="3" x2="12" y2="21"/><path d="M3 9l9-7 9 7"/><polyline points="3 9 3 16 12 21 21 16 21 9"/>
            </symbol>
            <symbol id="ic-zap" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
            </symbol>
            <symbol id="ic-list" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
            </symbol>
            <symbol id="ic-rocket" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2"/><path d="M12 8s-5.5 4-5.5 10h11C17.5 12 12 8 12 8z"/><line x1="12" y1="8" x2="12" y2="2"/><line x1="9" y1="11" x2="15" y2="11"/>
            </symbol>
            <symbol id="ic-clipboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
            </symbol>
            <symbol id="ic-logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="4" height="18"/><rect x="9" y="8" width="4" height="13"/><rect x="16" y="13" width="4" height="8"/>
            </symbol>
            <symbol id="ic-alert" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </symbol>
            <symbol id="ic-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </symbol>
            <symbol id="ic-file" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </symbol>
        </svg>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

        <!-- Navigation Tabs -->
        <div class="flex flex-wrap gap-6 overflow-x-auto pb-4 mb-16 border-b border-gray-100" style="margin-bottom: 4rem !important;">
            <button @click="activeTab = 'dashboard'" :class="{'active': activeTab === 'dashboard'}" class="fin-tab-btn mr-4">Dashboard</button>
            <button @click="activeTab = 'monitor'" :class="{'active': activeTab === 'monitor'}" class="fin-tab-btn mr-4">Operational Monitor</button>
            <button @click="activeTab = 'revenue'" :class="{'active': activeTab === 'revenue'}" class="fin-tab-btn mr-4">Revenue Engine</button>
            <button @click="activeTab = 'cost'" :class="{'active': activeTab === 'cost'}" class="fin-tab-btn mr-4">Cost Engine</button>
            <button @click="activeTab = 'calculator'" :class="{'active': activeTab === 'calculator'}" class="fin-tab-btn mr-4">Break-Even Calculator</button>
            <button @click="activeTab = 'reporting'" :class="{'active': activeTab === 'reporting'}" class="fin-tab-btn">Board Reporting</button>
        </div>

        <!-- 1. DASHBOARD -->
        <div x-show="activeTab === 'dashboard'" class="space-y-6">
            <div class="flex flex-nowrap overflow-x-auto gap-3 items-start no-scrollbar pb-2">
                <div class="fin-card kpi-small flex-1 min-w-[140px]">
                    <p class="fin-kpi-label">Total MRR</p>
                    <p class="fin-kpi-val text-2f4f4f">KES {{ number_format($mrr, 0) }}</p>
                </div>
                <div class="fin-card kpi-small flex-1 min-w-[140px]">
                    <p class="fin-kpi-label">ARR</p>
                    <p class="fin-kpi-val text-sm text-gray-500 font-bold">KES {{ number_format($arr, 0) }}</p>
                </div>
                <div class="fin-card kpi-small flex-1 min-w-[140px]">
                    <p class="fin-kpi-label">Gross Margin</p>
                    <p class="fin-kpi-val {{ $grossMargin < 70 ? 'text-red-600' : 'text-green-600' }}">{{ $grossMargin }}%</p>
                </div>
                <div class="fin-card kpi-small flex-1 min-w-[140px]">
                    <p class="fin-kpi-label">Net Margin</p>
                    <p class="fin-kpi-val {{ $netMargin < 0 ? 'text-red-600' : 'text-green-600' }}">{{ $netMargin }}%</p>
                </div>
                <div class="fin-card kpi-small flex-1 min-w-[140px]">
                    <p class="fin-kpi-label">Active Vehicles</p>
                    <p class="fin-kpi-val">{{ $activeVehicles }}</p>
                </div>
                <div class="fin-card kpi-small flex-1 min-w-[140px]">
                    <p class="fin-kpi-label">Break-even</p>
                    <p class="fin-kpi-val {{ $breakEvenProgress >= 100 ? 'text-green-600' : 'text-orange-500' }}">{{ $breakEvenProgress }}%</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="fin-card col-span-1 flex flex-col items-center justify-center text-center relative overflow-hidden">
                    <p class="fin-kpi-label mb-6">Financial Health Score</p>
                    
                    <div class="relative h-40 w-40 mb-4">
                        @php 
                            $healthColor = $healthScore < 50 ? '#ef4444' : ($healthScore < 80 ? '#f59e0b' : '#10b981');
                            $radiusHealth = 16;
                            $circumferenceHealth = 2 * pi() * $radiusHealth;
                            $dashHealth = ($healthScore / 100) * $circumferenceHealth;
                        @endphp
                        
                        <svg viewBox="0 0 36 36" class="h-full w-full">
                            <!-- Background ring -->
                            <circle cx="18" cy="18" r="{{ $radiusHealth }}" fill="none" stroke="#f1f5f9" stroke-width="4" />
                            
                            <!-- Health Score Fill -->
                            <circle cx="18" cy="18" r="{{ $radiusHealth }}" fill="none" 
                                    stroke="{{ $healthColor }}" stroke-width="4.5" 
                                    stroke-dasharray="{{ $dashHealth }} {{ $circumferenceHealth }}" 
                                    transform="rotate(-90 18 18)" 
                                    stroke-linecap="round" 
                                    class="transition-all duration-1000" />
                        </svg>
                        
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-[10px] text-gray-400 font-bold uppercase leading-none mb-1">Score</span>
                            <span class="text-3xl font-black leading-none" style="color: {{ $healthColor }}">{{ $healthScore }}</span>
                        </div>
                    </div>

                    <div class="w-full mt-4 border-t pt-4">
                        <div class="flex items-center justify-between px-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase">Status</span>
                            <span class="text-[10px] font-black uppercase" style="color: {{ $healthColor }}">
                                {{ $healthScore < 50 ? 'Critical' : ($healthScore < 80 ? 'Warning' : 'Healthy') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Gross Margin Breakdown (Professional Donut Chart) -->
                <div class="col-span-1 fin-card flex flex-col items-center justify-center relative overflow-hidden">
                    <p class="fin-kpi-label mb-6">Gross Margin & Cost Structure</p>
                    
                    <div class="relative h-40 w-40 mb-4">
                        @php 
                            $grossProfit = $mrr - ($varCloudCost + $varSmsCost);
                            $total = $mrr > 0 ? $mrr : 1; // Prevent div by zero
                            
                            $pGross = ($grossProfit / $total) * 100;
                            $pCloud = ($varCloudCost / $total) * 100;
                            $pSms   = ($varSmsCost / $total) * 100;
                            
                            // SVG calculations for donut arcs
                            $radius = 16;
                            $circumference = 2 * pi() * $radius;
                            
                            $dashGross = ($pGross / 100) * $circumference;
                            $dashCloud = ($pCloud / 100) * $circumference;
                            $dashSms   = ($pSms / 100) * $circumference;
                            
                            $offCloud = -($dashGross);
                            $offSms   = -($dashGross + $dashCloud);
                        @endphp
                        
                        <svg viewBox="0 0 36 36" class="h-full w-full">
                            <!-- Background ring -->
                            <circle cx="18" cy="18" r="{{ $radius }}" fill="none" stroke="#f1f5f9" stroke-width="4" />
                            
                            <!-- Gross Profit (Green/Slate) -->
                            <circle cx="18" cy="18" r="{{ $radius }}" fill="none" 
                                    stroke="#2f4f4f" stroke-width="4.5" 
                                    stroke-dasharray="{{ $dashGross }} {{ $circumference }}" 
                                    transform="rotate(-90 18 18)" 
                                    stroke-linecap="round" />
                                    
                            <!-- Cloud Costs (Blue) -->
                            <circle cx="18" cy="18" r="{{ $radius }}" fill="none" 
                                    stroke="#3b82f6" stroke-width="4.5" 
                                    stroke-dasharray="{{ $dashCloud }} {{ $circumference }}" 
                                    stroke-dashoffset="{{ $offCloud }}"
                                    transform="rotate(-90 18 18)" />

                            <!-- SMS Costs (Orange) -->
                            <circle cx="18" cy="18" r="{{ $radius }}" fill="none" 
                                    stroke="#f59e0b" stroke-width="4.5" 
                                    stroke-dasharray="{{ $dashSms }} {{ $circumference }}" 
                                    stroke-dashoffset="{{ $offSms }}"
                                    transform="rotate(-90 18 18)" />
                        </svg>
                        
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-[10px] text-gray-400 font-bold uppercase leading-none mb-1">Margin</span>
                            <span class="text-xl font-black text-[#2f4f4f] leading-none">{{ round($pGross, 1) }}%</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2 w-full mt-4 border-t pt-4">
                        <div class="text-center">
                            <p class="text-[8px] font-bold text-[#2f4f4f] uppercase mb-1">Gross Profit</p>
                            <div class="h-1.5 w-full bg-[#2f4f4f] rounded-full mb-1"></div>
                            <p class="text-[10px] font-black">{{ round($pGross, 1) }}%</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[8px] font-bold text-[#3b82f6] uppercase mb-1">Cloud</p>
                            <div class="h-1.5 w-full bg-[#3b82f6] rounded-full mb-1"></div>
                            <p class="text-[10px] font-black">{{ round($pCloud, 1) }}%</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[8px] font-bold text-[#f59e0b] uppercase mb-1">SMS</p>
                            <div class="h-1.5 w-full bg-[#f59e0b] rounded-full mb-1"></div>
                            <p class="text-[10px] font-black">{{ round($pSms, 1) }}%</p>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 space-y-4">
                    <div class="fin-card">
                        <p class="fin-kpi-label">Strategic Decision Panel</p>
                        <div class="space-y-3">
                            @if($grossMargin < 70)
                                <div class="alert-pill bg-red-50 text-red-700 border border-red-100">
                                    <span class="h-2 w-2 rounded-full bg-red-600 animate-pulse"></span>
                                    Infrastructure Optimization Needed (Margin < 70%)
                                </div>
                            @else
                                <div class="alert-pill bg-green-50 text-green-700 border border-green-100">
                                    <span class="h-2 w-2 rounded-full bg-green-600"></span>
                                    Healthy Infrastructure Margins
                                </div>
                            @endif

                            @if($churnRate > 5)
                                <div class="alert-pill bg-red-50 text-red-700 border border-red-100">
                                    <span class="h-2 w-2 rounded-full bg-red-600"></span>
                                    Retention Risk Alert (Churn > 5%)
                                </div>
                            @else
                                <div class="alert-pill bg-green-50 text-green-700 border border-green-100">
                                    <span class="h-2 w-2 rounded-full bg-green-600"></span>
                                    Customer Retention is Stable
                                </div>
                            @endif

                            @if($breakEvenProgress >= 100)
                                <div class="alert-pill bg-green-50 text-green-700 border border-green-100">
                                    <span class="h-2 w-2 rounded-full bg-green-600"></span>
                                    Expansion Ready Indicator: MRR > Break-even
                                </div>
                            @else
                                <div class="alert-pill bg-orange-50 text-orange-700 border border-orange-100">
                                    <span class="h-2 w-2 rounded-full bg-orange-600"></span>
                                    Currently scaling towards Break-even ({{ $breakEvenProgress }}%)
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="fin-card">
                            <p class="fin-kpi-label">Runway</p>
                            <p class="text-xl font-bold">{{ $cashRunway }} Months</p>
                        </div>
                        <div class="fin-card">
                            <p class="fin-kpi-label">Break-even Veh</p>
                            <p class="text-xl font-bold">{{ $breakEvenVehicles }}</p>
                        </div>
                        <div class="fin-card">
                            <p class="fin-kpi-label">CM / Vehicle</p>
                            <p class="text-xl font-bold">KES {{ number_format($revenuePerVehicle - $variableCostPerVehicle, 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. OPERATIONAL DATA MONITOR -->
        <div x-show="activeTab === 'monitor'" class="space-y-6">
            <div class="grid gap-6 md:grid-cols-3">
                <div class="fin-card">
                    <h3 class="font-bold text-sm border-b pb-2 mb-4">Fleet Activity Metrics</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Live Vehicles</span><span class="font-bold">{{ $activeVehicles }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Total Registered</span><span class="font-bold">{{ $totalVehicles }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Active Companies</span><span class="font-bold">{{ $revenueByTier->sum('count') }}</span></div>
                    </div>
                </div>
                <div class="fin-card">
                    <h3 class="font-bold text-sm border-b pb-2 mb-4">Revenue Events (API Usage)</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">API Reqs (24h)</span><span class="font-bold text-blue-600">{{ $apiUsageToday }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Total AI Consumption</span><span class="font-bold">{{ number_format($apiUsageTotal) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Infrastructure Efficiency</span><span class="font-bold text-green-600">High</span></div>
                    </div>
                </div>
                <div class="fin-card">
                    <h3 class="font-bold text-sm border-b pb-2 mb-4">Cost Events</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Cloud Cost / Veh</span><span class="font-bold text-red-500">KES {{ number_format($variableCostPerVehicle * 0.75, 0) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">SMS Cost / Veh</span><span class="font-bold text-red-500">KES {{ number_format($variableCostPerVehicle * 0.25, 0) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-500">Fixed Overhead</span><span class="font-bold text-red-600">KES {{ number_format($fixedMonthlyCost, 0) }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. REVENUE ENGINE -->
        <div x-show="activeTab === 'revenue'" class="space-y-6">
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Pie Chart: Revenue by Tier (Professional Donut) -->
                <div class="fin-card flex flex-col items-center justify-center relative overflow-hidden">
                    <h3 class="fin-kpi-label mb-6">MRR Breakdown by Tier</h3>
                    
                    <div class="relative h-44 w-44 mb-4">
                        @php 
                            $tierColors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6'];
                            $radiusTier = 16;
                            $circumferenceTier = 2 * pi() * $radiusTier;
                            $currentOffset = 0;
                        @endphp
                        
                        <svg viewBox="0 0 36 36" class="h-full w-full">
                            <!-- Background ring -->
                            <circle cx="18" cy="18" r="{{ $radiusTier }}" fill="none" stroke="#f1f5f9" stroke-width="4" />
                            
                            @foreach($revenueByTier as $index => $tier)
                                @php 
                                    $percent = $mrr > 0 ? ($tier->mrr_total / $mrr) * 100 : 0; 
                                    $dash = ($percent / 100) * $circumferenceTier;
                                @endphp
                                <circle cx="18" cy="18" r="{{ $radiusTier }}" fill="none" 
                                        stroke="{{ $tierColors[$index % count($tierColors)] }}" stroke-width="4.5" 
                                        stroke-dasharray="{{ $dash }} {{ $circumferenceTier }}" 
                                        stroke-dashoffset="{{ -$currentOffset }}"
                                        transform="rotate(-90 18 18)" 
                                        class="transition-all duration-1000" />
                                @php $currentOffset += $dash; @endphp
                            @endforeach
                        </svg>
                        
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-[10px] text-gray-400 font-bold uppercase leading-none mb-1">Total MRR</span>
                            <span class="text-lg font-black text-[#2f4f4f] leading-none">KES {{ number_format($mrr/1000, 1) }}k</span>
                        </div>
                    </div>

                    <div class="w-full mt-4 border-t pt-4 space-y-2">
                        @foreach($revenueByTier as $index => $tier)
                            <div class="flex items-center justify-between px-2">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full" style="background: {{ $tierColors[$index % count($tierColors)] }}"></span>
                                    <span class="text-[10px] font-bold text-gray-500 uppercase">{{ $tier->plan_name }}</span>
                                </div>
                                <span class="text-[10px] font-black">KES {{ number_format($tier->mrr_total, 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Churn Trend Graph (Simplified Bar) -->
                <div class="fin-card">
                    <h3 class="font-bold text-sm mb-6">📉 Churn Rate Monitoring</h3>
                    <div class="flex items-end gap-2 h-24 mb-4">
                        @foreach($churnTrend as $val)
                            <div class="flex-1 bg-red-100 hover:bg-red-200 transition-colors rounded-t-sm relative group" style="height: {{ $val * 15 }}px">
                                <div class="absolute -top-6 left-1/2 -translate-x-1/2 hidden group-hover:block bg-gray-800 text-white text-[10px] px-1.5 py-0.5 rounded">{{ $val }}%</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-400 font-bold uppercase">
                        <span>Month -3</span><span>Month -2</span><span>Month -1</span><span class="text-red-600">Current</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <!-- ARPU Analysis -->
                <div class="fin-card">
                    <p class="fin-kpi-label">Average Revenue (ARPU)</p>
                    <p class="fin-kpi-val">KES {{ number_format($arpu, 0) }}</p>
                    <p class="text-[10px] text-gray-400 mt-2">Revenue per active customer</p>
                </div>

                <!-- Expansion Revenue -->
                <div class="fin-card">
                    <p class="fin-kpi-label">Expansion Revenue</p>
                    <p class="fin-kpi-val text-green-600">KES {{ number_format($expansionRevenue, 0) }}</p>
                    <p class="text-[10px] text-green-600 font-bold mt-2">↑ From upgrades & add-ons</p>
                </div>

                <!-- CAC Tracking -->
                <div class="fin-card">
                    <p class="fin-kpi-label">CAC tracking</p>
                    <p class="fin-kpi-val">KES {{ number_format($cac, 0) }}</p>
                    <p class="text-[10px] text-orange-600 font-bold mt-2">Acquisition cost per company</p>
                </div>
            </div>

            <!-- LTV calculation display -->
            <div class="fin-card shadow-lg" style="background-color: #ffffff !important; border: 1px solid #f3f4f6 !important; color: #111827 !important;">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest mb-1" style="color: #6b7280 !important;">Lifetime Value (LTV)</h3>
                        <p class="text-3xl font-black" style="color: #2f4f4f !important;">KES {{ number_format($ltv ?? 0, 0) }}</p>
                    </div>
                    <div class="text-right">
                        <h3 class="text-xs font-bold uppercase tracking-widest mb-1" style="color: #6b7280 !important;">LTV / CAC Ratio</h3>
                        <p class="text-3xl font-black" style="color: #10b981 !important;">{{ number_format($ltvCacRatio ?? 0, 1) }}x</p>
                    </div>
                </div>
                <div class="mt-6 h-1.5 w-full rounded-full overflow-hidden" style="background-color: #f3f4f6 !important;">
                    <div class="h-full" style="background-color: #10b981 !important; width: {{ min(100, ($ltvCacRatio ?? 0) * 10) }}%"></div>
                </div>
                <p class="mt-3 text-[10px] italic" style="color: #94a3b8 !important;">Efficiency benchmark: > 3.0x is considered healthy for SaaS expansion. (Based on KES {{ number_format($cac ?? 0, 0) }} CAC)</p>
            </div>
        </div>

        <!-- 4. COST ENGINE -->
        <div x-show="activeTab === 'cost'" class="space-y-6">
            <div class="grid gap-6 md:grid-cols-3">
                <div class="fin-card col-span-2">
                    <h3 class="font-bold text-sm mb-6">Cloud Cost Allocation</h3>
                    <div class="h-4 w-full bg-gray-100 rounded-full overflow-hidden flex">
                        <div class="h-full bg-blue-500" style="width: 60%"></div>
                        <div class="h-full bg-purple-500" style="width: 25%"></div>
                        <div class="h-full bg-orange-500" style="width: 15%"></div>
                    </div>
                    <div class="mt-4 flex gap-6">
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-blue-500"></span> <span class="text-xs text-gray-600">Servers (60%)</span></div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-purple-500"></span> <span class="text-xs text-gray-600">Database (25%)</span></div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-orange-500"></span> <span class="text-xs text-gray-600">AI Tokens (15%)</span></div>
                    </div>
                </div>
                <div class="fin-card">
                    <h3 class="font-bold text-sm mb-4">Cost Efficiency</h3>
                    <p class="text-3xl font-extrabold text-green-600">92%</p>
                    <p class="text-xs text-gray-500 mt-1">Infrastructure Efficiency Score</p>
                </div>
            </div>
        </div>

        <!-- 5. BREAK-EVEN CALCULATOR -->
        <div x-show="activeTab === 'calculator'" class="space-y-6">
            <div class="fin-card">
                <h2 class="text-lg font-bold mb-8">Financial Growth Simulator</h2>
                <div class="grid gap-10 md:grid-cols-2">
                    <div class="space-y-8">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-3">Fixed Monthly Cost (KES)</label>
                            <input type="number" x-model.number="fixedCost" class="w-full rounded-xl border-gray-200 py-3 shadow-sm focus:border-[#2f4f4f] focus:ring-[#2f4f4f]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-3">Revenue Per Vehicle (KES)</label>
                            <input type="number" x-model.number="revPerVeh" class="w-full rounded-xl border-gray-200 py-3 shadow-sm focus:border-[#2f4f4f] focus:ring-[#2f4f4f]">
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <label class="text-xs font-bold text-gray-400 uppercase">Simulate Vehicle Growth</label>
                                <span class="text-sm font-bold text-[#2f4f4f] bg-gray-100 px-3 py-1 rounded-full" x-text="simVehicles + ' Vehicles'"></span>
                            </div>
                            <input type="range" x-model.number="simVehicles" min="0" max="5000" step="10" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-[#2f4f4f]">
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-8 border border-gray-100 shadow-inner">
                        <p class="text-xs text-gray-400 uppercase font-bold mb-6">Live Simulation Outcomes</p>
                        <div class="space-y-5">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Contribution Margin:</span>
                                <span class="font-bold" x-text="'KES ' + contributionMargin.toLocaleString()"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Break-even Vehicle Count:</span>
                                <span class="font-bold text-blue-600" x-text="breakEvenCount"></span>
                            </div>
                            <div class="flex justify-between items-center border-t border-gray-200 pt-4">
                                <span class="text-sm text-gray-600">Projected Net Margin:</span>
                                <span :class="projectedMargin >= 0 ? 'text-green-600' : 'text-red-600'" class="text-2xl font-black" x-text="projectedMargin + '%'"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Projected Monthly Profit:</span>
                                <span :class="projectedProfit >= 0 ? 'text-green-600' : 'text-red-600'" class="text-3xl font-black" x-text="'KES ' + projectedProfit.toLocaleString()"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. BOARD REPORTING -->
        <div x-show="activeTab === 'reporting'" class="space-y-0">
            <!-- HEADER -->
            <header class="header-report">
                <div class="h-left">
                    <div class="h-logo">
                        <div class="h-logo-mark"><svg class="svg-ico-lg" style="color:#fff;"><use href="#ic-logo"/></svg></div>
                        <div class="h-brand">BoardIQ</div>
                    </div>
                    <div class="h-divider"></div>
                    <div class="h-meta">
                        <div class="h-title">Board-Ready Financial Insights</div>
                        <div class="h-sub">Quarterly Financial &amp; Risk Overview</div>
                    </div>
                </div>
                <div class="h-right">
                    <div class="h-badge">{{ 'Q' . now()->quarter . ' ' . now()->year }} · {{ now()->startOfQuarter()->format('M') }}–{{ now()->endOfQuarter()->format('M') }}</div>
                    <button class="btn-report btn-outline-report" wire:click="downloadPdf"><svg class="svg-ico-sm" style="color:currentColor"><use href="#ic-file"/></svg> Download PDF</button>
                    <button class="btn-report btn-green-report" wire:click="exportExcel"><svg class="svg-ico-sm" style="color:#fff"><use href="#ic-download"/></svg> Export Excel</button>
                </div>
            </header>

            <div class="page-report">
                <!-- REPORT STRIP -->
                <div class="report-strip">
                    <div class="rs-left">
                        <div class="rs-eyebrow">Automated Quarterly Summary</div>
                        <div class="rs-title">Quarterly Financial &amp; Risk Overview</div>
                        <div class="rs-desc">Automated summary of growth metrics, profitability, cash position, risk exposure, and expansion readiness for board review.</div>
                    </div>
                    <div class="rs-right">
                        <div class="qtr-chip">{{ 'Q' . now()->subMonths(6)->quarter . " '" . now()->subMonths(6)->format('y') }}</div>
                        <div class="qtr-chip">{{ 'Q' . now()->subMonths(3)->quarter . " '" . now()->subMonths(3)->format('y') }}</div>
                        <div class="qtr-chip active">{{ 'Q' . now()->quarter . " '" . now()->format('y') }}</div>
                        <div class="gen-date">Generated {{ now()->format('M j, Y') }}</div>
                    </div>
                </div>

                <!-- HEALTH BANNER -->
                <div class="health-banner">
                    <div class="hb-score">
                        <div class="hb-score-val">{{ $healthScore }}</div>
                        <div class="hb-score-lbl">Score</div>
                    </div>
                    <div class="hb-center">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                            <div class="hb-title">Overall Business Health Score — {{ $healthScore < 50 ? 'Critical' : ($healthScore < 80 ? 'Warning' : 'Healthy') }}</div>
                            @if($healthScore < 50)
                            <div class="hb-status-chip">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                Action Required
                            </div>
                            @endif
                        </div>
                        <div class="hb-desc">Based on weighted indicators: revenue growth, margin, runway, churn, LTV:CAC, burn rate, and expansion readiness.</div>
                        <div class="hb-bar-wrap">
                            <div class="hb-bar"><div class="hb-bar-fill" style="width:{{ $healthScore }}%"></div></div>
                            <div class="hb-bar-pct">{{ $healthScore }} / 100</div>
                        </div>
                    </div>
                    <div class="hb-right">
                        <div class="hb-trend-lbl">Health Trend</div>
                        @php 
                            $trendVal = count($grossMarginTrend) > 1 ? round(end($grossMarginTrend) - $grossMarginTrend[0], 0) : 0;
                        @endphp
                        <div class="hb-trend-val">{{ $trendVal >= 0 ? '+' : '' }}{{ $trendVal }}%</div>
                        <div class="hb-trend-sub">{{ $trendVal >= 0 ? 'Improving' : 'Declining' }}</div>
                    </div>
                </div>

                <!-- ══ KPIs ══ -->
                <div class="section-lbl-new">Key Performance Indicators</div>
                <div class="kpi-strip-new">
                    <div class="kpi-new">
                        <div class="kpi-top-new" style="background:var(--green)"></div>
                        <div class="kpi-icon-row-new">
                            <div class="kpi-icon-new" style="background:var(--green-bg);color:var(--green)"><svg class="svg-ico"><use href="#ic-trending-up"/></svg></div>
                            <span class="tag-new tag-green-new">↑ vs Previous</span>
                        </div>
                        <div class="kpi-lbl-new">MRR Growth</div>
                        <div class="kpi-val-new">+{{ $mrrGrowth }}%</div>
                    </div>

                    <div class="kpi-new">
                        <div class="kpi-top-new" style="background:var(--blue)"></div>
                        <div class="kpi-icon-row-new">
                            <div class="kpi-icon-new" style="background:var(--blue-bg);color:var(--blue)"><svg class="svg-ico"><use href="#ic-truck"/></svg></div>
                            <span class="tag-new tag-green-new">↑ L30D</span>
                        </div>
                        <div class="kpi-lbl-new">Fleet Growth</div>
                        <div class="kpi-val-new">+{{ $fleetGrowth }}%</div>
                    </div>

                    <div class="kpi-new">
                        <div class="kpi-top-new" style="background:var(--amber)"></div>
                        <div class="kpi-icon-row-new">
                            <div class="kpi-icon-new" style="background:var(--amber-bg);color:var(--amber)"><svg class="svg-ico"><use href="#ic-currency"/></svg></div>
                            <span class="tag-new tag-gray-new">— ARR Projection</span>
                        </div>
                        <div class="kpi-lbl-new">Annual Run Rate</div>
                        <div class="kpi-val-new" style="color:{{ $arr > 0 ? 'var(--ink)' : 'var(--muted)' }};">KES {{ number_format($arr/1000000, 1) }}M</div>
                    </div>

                    <div class="kpi-new">
                        <div class="kpi-top-new" style="background:{{ $netProfit >= 0 ? 'var(--green)' : 'var(--red)' }}"></div>
                        <div class="kpi-icon-row-new">
                            <div class="kpi-icon-new" style="background:{{ $netProfit >= 0 ? 'var(--green-bg)' : 'var(--red-bg)' }};color:{{ $netProfit >= 0 ? 'var(--green)' : 'var(--red)' }}"><svg class="svg-ico"><use href="{{ $netProfit >= 0 ? '#ic-trending-up' : '#ic-trending-down' }}"/></svg></div>
                            <span class="tag-new {{ $netProfit >= 0 ? 'tag-green-new' : 'tag-red-new' }}">Net after costs</span>
                        </div>
                        <div class="kpi-lbl-new">Monthly Profit</div>
                        <div class="kpi-val-new" style="color:{{ $netProfit >= 0 ? 'var(--green)' : 'var(--red)' }};">KES {{ number_format($netProfit/1000, 0) }}K</div>
                    </div>

                    <div class="kpi-new">
                        <div class="kpi-top-new" style="background:{{ is_numeric($cashRunway) && $cashRunway < 3 ? 'var(--red)' : 'var(--green)' }}"></div>
                        <div class="kpi-icon-row-new">
                            <div class="kpi-icon-new" style="background:{{ is_numeric($cashRunway) && $cashRunway < 3 ? 'var(--red-bg)' : 'var(--green-bg)' }};color:{{ is_numeric($cashRunway) && $cashRunway < 3 ? 'var(--red)' : 'var(--green)' }}"><svg class="svg-ico"><use href="#ic-clock"/></svg></div>
                            <span class="tag-new {{ is_numeric($cashRunway) && $cashRunway < 3 ? 'tag-red-new' : 'tag-green-new' }}">Remaining</span>
                        </div>
                        <div class="kpi-lbl-new">Cash Runway</div>
                        <div class="kpi-val-new" style="color:{{ is_numeric($cashRunway) && $cashRunway < 3 ? 'var(--red)' : 'var(--green)' }};">{{ is_numeric($cashRunway) ? $cashRunway : 0 }} mo</div>
                    </div>
                </div>

                <!-- ══ GROWTH & REVENUE ══ -->
                <div class="section-lbl-new">Growth &amp; Revenue</div>
                <div class="g53-new" style="margin-bottom:14px;">
                    <!-- Growth Metrics -->
                    <div class="card-new">
                        <div class="card-hdr-new">
                            <div class="card-title-new">
                                <div class="ico-new" style="background:var(--green-bg);color:var(--green);"><svg class="svg-ico"><use href="#ic-trending-up"/></svg></div>
                                Growth Metrics
                            </div>
                            <div class="card-meta-new">L30D Performance</div>
                        </div>
                        <div class="card-body-new">
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">MRR Growth Rate</span>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span class="tag-new tag-green-new">+{{ $mrrGrowth }}%</span>
                                    <span class="mrow-val-new green">Strong</span>
                                </div>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Fleet / Vehicle Growth (L30D)</span>
                                <span class="tag-new tag-green-new">+{{ $fleetGrowth }}%</span>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">New Client Activations</span>
                                <span class="mrow-val-new" style="font-weight:700;">{{ $newCompaniesCount }} Companies</span>
                            </div>

                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Revenue per Vehicle</span>
                                <span class="mrow-val-new" style="font-family:var(--mono);">KES {{ number_format($revenuePerVehicle, 0) }}</span>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Average Contract Value (Monthly)</span>
                                <span class="mrow-val-new" style="font-family:var(--mono);">KES {{ number_format($arpu, 0) }}</span>
                            </div>

                            <div class="spark-wrap-new">
                                <div class="spark-lbl-new">Gross Margin Trend (6 Months)</div>
                                <svg width="100%" height="44" viewBox="0 0 500 44" preserveAspectRatio="none" style="display:block;">
                                    <defs>
                                        <linearGradient id="sg" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#059669" stop-opacity="0.15"/>
                                            <stop offset="100%" stop-color="#059669" stop-opacity="0"/>
                                        </linearGradient>
                                    </defs>
                                    @php 
                                        $pts = [];
                                        foreach($grossMarginTrend as $idx => $val) {
                                            $x = ($idx / 3) * 500;
                                            $y = 44 - ($val / 100 * 44);
                                            $pts[] = "$x,$y";
                                        }
                                        $path = count($pts) > 0 ? implode(' L ', $pts) : "0,44 L 500,44";
                                    @endphp
                                    <path d="M {{ $path }}" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M 0,44 L {{ $path }} L 500,44 Z" fill="url(#sg)"/>
                                    <circle cx="500" cy="{{ end($pts) ? explode(',', end($pts))[1] : 44 }}" r="4" fill="#059669"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- ARR & Profitability -->
                    <div class="card-new">
                        <div class="card-hdr-new">
                            <div class="card-title-new">
                                <div class="ico-new" style="background:var(--amber-bg);color:var(--amber);"><svg class="svg-ico"><use href="#ic-currency"/></svg></div>
                                ARR &amp; Profitability
                            </div>
                        </div>
                        <div class="card-body-new" style="padding-top:14px;">
                            <div class="arr-center-new">
                                <div class="arr-lbl-new">Annual Run Rate</div>
                                <div class="arr-val-new">KES {{ number_format($arr/1000000, 1) }}M</div>
                                <span class="tag-new tag-amber-new" style="margin-top:8px;display:inline-flex;">Expanding Base</span>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Monthly Profit / Loss</span>
                                <span class="mrow-val-new {{ $netProfit >= 0 ? 'green' : 'red' }}" style="font-family:var(--mono);">KES {{ number_format($netProfit, 0) }}</span>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Gross Margin</span>
                                <span class="mrow-val-new" style="font-family:var(--mono);">{{ $grossMargin }}%</span>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Burn Rate</span>
                                <span class="mrow-val-new red" style="font-family:var(--mono);">KES {{ number_format($fixedMonthlyCost, 0) }}/mo</span>
                            </div>
                            <div class="mrow-new" style="margin-top:4px;">
                                <span class="mrow-lbl-new">Revenue vs Target</span>
                                <span class="mrow-val-new muted">{{ round(($mrr / max(1, $fixedMonthlyCost)) * 100, 0) }}%</span>
                            </div>
                            <div class="progress-wrap-new" style="margin-top:10px;">
                                <div class="progress-hdr-new"><span>Revenue vs Target</span><span>{{ round(($mrr / max(1, $fixedMonthlyCost)) * 100, 0) }}%</span></div>
                                <div class="progress-track-new"><div class="progress-fill-new" style="width:{{ min(100, ($mrr / max(1, $fixedMonthlyCost)) * 100) }}%;background:var(--amber);"></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ CASH POSITION & RISK ══ -->
                <div class="section-lbl-new">Cash Position &amp; Risk</div>
                <div class="g3-new" style="margin-bottom:14px;">
                    <!-- Cash Runway -->
                    <div class="card-new">
                        <div class="card-hdr-new">
                            <div class="card-title-new">
                                <div class="ico-new" style="background:var(--red-bg);color:var(--red);"><svg class="svg-ico"><use href="#ic-shield"/></svg></div>
                                Cash Runway
                            </div>
                            <span class="tag-new tag-red-new">Critical</span>
                        </div>
                        <div class="card-body-new">
                            <div class="runway-center-new">
                                <div class="runway-num-new">{{ is_numeric($cashRunway) ? round($cashRunway, 0) : 0 }}</div>
                                <div class="runway-unit-new">Months remaining</div>
                            </div>
                            <div class="runway-alert-new">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                Immediate funding required
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Monthly Burn</span>
                                <span class="mrow-val-new" style="font-family:var(--mono);">KES {{ number_format($fixedMonthlyCost, 0) }}</span>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Net Monthly Loss</span>
                                <span class="mrow-val-new red" style="font-family:var(--mono);">KES {{ number_format(abs($netProfit), 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Unit Economics -->
                    <div class="card-new">
                        <div class="card-hdr-new">
                            <div class="card-title-new">
                                <div class="ico-new" style="background:var(--blue-bg);color:var(--blue);"><svg class="svg-ico"><use href="#ic-scale"/></svg></div>
                                Unit Economics
                            </div>
                            <div class="card-meta-new">LTV / CAC</div>
                        </div>
                        <div class="card-body-new">
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Acquisition Cost (CAC)</span>
                                <span class="mrow-val-new" style="font-family:var(--mono);">KES {{ number_format($cac, 0) }}</span>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Lifetime Value (LTV)</span>
                                <span class="mrow-val-new" style="font-family:var(--mono);">KES {{ number_format($ltv, 0) }}</span>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">LTV : CAC Ratio</span>
                                <span class="mrow-val-new amber" style="font-family:var(--mono);font-size:15px;font-weight:700;">{{ $ltvCacRatio }}x</span>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">Churn Rate</span>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span class="tag-new tag-green-new">{{ $churnRate }}% — Stable</span>
                                </div>
                            </div>
                            <div class="mrow-new">
                                <span class="mrow-lbl-new">LTV:CAC Target (3:1)</span>
                                <span class="mrow-val-new muted">{{ round(($ltvCacRatio / 3) * 100, 0) }}%</span>
                            </div>
                            <div class="progress-wrap-new" style="margin-top:12px;">
                                <div class="progress-hdr-new"><span>Progress to 3:1 Target</span><span>{{ $ltvCacRatio }}x / 3x</span></div>
                                <div class="progress-track-new"><div class="progress-fill-new" style="width:{{ min(100, ($ltvCacRatio / 3) * 100) }}%;background:var(--blue);"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Risk Overview -->
                    <div class="card-new">
                        <div class="card-hdr-new">
                            <div class="card-title-new">
                                <div class="ico-new" style="background:#fef9f0;color:var(--amber);"><svg class="svg-ico"><use href="#ic-zap"/></svg></div>
                                Risk Overview
                            </div>
                            <div class="card-meta-new">Tracked Items</div>
                        </div>
                        <div class="card-body-new" style="padding:14px 18px;">
                            <div class="risk-item-new">
                                <div class="risk-dot-new" style="background:var(--red)"></div>
                                <div class="risk-body-new">
                                    <div class="risk-title-new">Zero Cash Runway</div>
                                    <div class="risk-desc-new">Monthly burn of KES {{ number_format($fixedMonthlyCost/1000, 0) }}K with insufficient reserves.</div>
                                    <div class="risk-sev-new" style="color:var(--red);">● Critical</div>
                                </div>
                            </div>
                            <div class="risk-item-new">
                                <div class="risk-dot-new" style="background:var(--amber)"></div>
                                <div class="risk-body-new">
                                    <div class="risk-title-new">LTV:CAC Threshold</div>
                                    <div class="risk-desc-new">Current ratio of {{ $ltvCacRatio }}x is below the 3.0x SaaS healthy benchmark.</div>
                                    <div class="risk-sev-new" style="color:var(--amber);">● High Risk</div>
                                </div>
                            </div>
                            <div class="risk-item-new">
                                <div class="risk-dot-new" style="background:var(--green)"></div>
                                <div class="risk-body-new">
                                    <div class="risk-title-new">Infrastructure Efficiency</div>
                                    <div class="risk-desc-new">Costs are stable at KES {{ number_format($variableCostPerVehicle, 0) }} per active vehicle.</div>
                                    <div class="risk-sev-new" style="color:var(--green);">● Low Risk</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ QUARTERLY COMPARISON & EXPANSION ══ -->
                <div class="section-lbl-new">Quarterly Comparison &amp; Expansion</div>
                <div class="g32-new" style="margin-bottom:14px;">
                    <!-- Comparison Table -->
                    <div class="card-new">
                        <div class="card-hdr-new">
                            <div class="card-title-new">
                                <div class="ico-new" style="background:var(--surface);color:var(--muted);"><svg class="svg-ico"><use href="#ic-list"/></svg></div>
                                Quarterly Comparison
                            </div>
                            <div class="card-meta-new">Historical Trend</div>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="ctable-new">
                                <thead>
                                    <tr>
                                        <th>Metric</th>
                                        <th>Baseline</th>
                                        <th>Current</th>
                                        <th>Δ Delta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>MRR Growth</td>
                                        <td class="flat-new">—</td>
                                        <td class="up-new text-green-600">+{{ $mrrGrowth }}%</td>
                                        <td class="up-new text-green-600">↑ New</td>
                                    </tr>
                                    <tr>
                                        <td>Monthly Profit</td>
                                        <td class="flat-new">—</td>
                                        <td class="dn-new {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">KES {{ number_format($netProfit, 0) }}</td>
                                        <td class="flat-new">—</td>
                                    </tr>
                                    <tr>
                                        <td>Gross Margin</td>
                                        <td class="flat-new">—</td>
                                        <td>{{ $grossMargin }}%</td>
                                        <td class="flat-new">—</td>
                                    </tr>
                                    <tr>
                                        <td>Churn Rate</td>
                                        <td>0%</td>
                                        <td class="up-new text-green-600">{{ $churnRate }}%</td>
                                        <td class="up-new text-green-600">✓ Stable</td>
                                    </tr>
                                    <tr>
                                        <td>Health Score</td>
                                        <td class="flat-new">—</td>
                                        <td>{{ $healthScore }}</td>
                                        <td style="color:var(--teal);">Improving</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Expansion Readiness -->
                    <div class="card-new">
                        <div class="card-hdr-new">
                            <div class="card-title-new">
                                <div class="ico-new" style="background:var(--amber-bg);color:var(--amber);"><svg class="svg-ico"><use href="#ic-rocket"/></svg></div>
                                Expansion Readiness
                            </div>
                        </div>
                        <div class="card-body-new" style="padding-top:14px;">
                            <div class="exp-center-new">
                                <div class="exp-circle-new" style="font-size:0;">
                                    @if($healthScore > 60)
                                    <span style="font-size: 32px;">🚀</span>
                                    @else
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                    @endif
                                </div>
                                <div class="exp-label-new {{ $healthScore > 60 ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ $healthScore > 60 ? 'Green — Expansion Ready' : 'Amber — Proceed with Caution' }}
                                </div>
                                <div class="exp-desc-new">Based on LTV/CAC ratio of {{ $ltvCacRatio }}x and gross margins of {{ $grossMargin }}%, the platform is {{ $healthScore > 60 ? 'exceeding' : 'nearing' }} the efficiency required for regional expansion.</div>
                            </div>
                            <div style="border-top:1px solid var(--border);padding:12px 0 0;">
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;color:var(--muted);margin-bottom:8px;">
                                    <span style="font-weight:600;color:var(--ink);">Readiness Checklist</span>
                                    <span class="tag-new tag-amber-new">{{ $healthScore > 60 ? '4 / 5' : '3 / 5' }}</span>
                                </div>
                                <div class="progress-track-new" style="margin-bottom:12px;">
                                    <div class="progress-fill-new" style="width:{{ $healthScore }}%;background:{{ $healthScore > 60 ? 'var(--green)' : 'var(--amber)' }};"></div>
                                </div>
                                <div class="checklist-row-new pass">✓ Zero churn recorded</div>
                                <div class="checklist-row-new pass">✓ Fleet growth positive</div>
                                <div class="checklist-row-new pass">✓ MRR growth achieved</div>
                                <div class="checklist-row-new {{ $healthScore > 80 ? 'pass' : 'fail' }}">{{ $healthScore > 80 ? '✓' : '✗' }} Cash runway sufficient</div>
                                <div class="checklist-row-new {{ $ltvCacRatio > 3 ? 'pass' : 'fail' }}">{{ $ltvCacRatio > 3 ? '✓' : '✗' }} Positive unit economics pending</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ BOARD ACTION ITEMS ══ -->
                <div class="section-lbl-new">Board Action Items</div>
                <div class="g3-new" style="margin-bottom:14px;">
                    <!-- Immediate Priority -->
                    <div class="card-new" style="border-top:3px solid var(--red);">
                        <div class="card-hdr-new" style="background:var(--red-bg);">
                            <div class="card-title-new">
                                <span style="width:9px;height:9px;border-radius:50%;background:var(--red);display:inline-block;flex-shrink:0;"></span>
                                Immediate Priority
                            </div>
                            <span class="tag-new tag-red-new">This Quarter</span>
                        </div>
                        <div class="card-body-new" style="padding:0;">
                            <div class="mrow-new" style="padding:11px 18px;">
                                <span class="mrow-lbl-new">Secure bridge funding</span>
                                <span class="tag-new tag-red-new">Critical</span>
                            </div>
                            <div class="mrow-new" style="padding:11px 18px;">
                                <span class="mrow-lbl-new">Establish cash reserves</span>
                                <span class="tag-new tag-red-new">Critical</span>
                            </div>
                            <div class="mrow-new" style="padding:11px 18px;">
                                <span class="mrow-lbl-new">Launch active MRR contracts</span>
                                <span class="tag-new tag-amber-new">High</span>
                            </div>
                        </div>
                    </div>

                    <!-- Short-Term Focus -->
                    <div class="card-new" style="border-top:3px solid var(--amber);">
                        <div class="card-hdr-new" style="background:var(--amber-bg);">
                            <div class="card-title-new">
                                <span style="width:9px;height:9px;border-radius:50%;background:var(--amber);display:inline-block;flex-shrink:0;"></span>
                                Short-Term Focus
                            </div>
                            <span class="tag-new tag-amber-new">90 Days</span>
                        </div>
                        <div class="card-body-new" style="padding:0;">
                            <div class="mrow-new" style="padding:11px 18px;">
                                <span class="mrow-lbl-new">Optimize CAC metrics</span>
                                <span class="tag-new tag-amber-new">High</span>
                            </div>
                            <div class="mrow-new" style="padding:11px 18px;">
                                <span class="mrow-lbl-new">Gross margin targets</span>
                                <span class="tag-new tag-amber-new">High</span>
                            </div>
                            <div class="mrow-new" style="padding:11px 18px;">
                                <span class="mrow-lbl-new">Finalize pilot SLAs</span>
                                <span class="tag-new tag-blue-new">Medium</span>
                            </div>
                        </div>
                    </div>

                    <!-- Strategic Goals -->
                    <div class="card-new" style="border-top:3px solid var(--green);">
                        <div class="card-hdr-new" style="background:var(--green-bg);">
                            <div class="card-title-new">
                                <span style="width:9px;height:9px;border-radius:50%;background:var(--green);display:inline-block;flex-shrink:0;"></span>
                                Strategic Goals
                            </div>
                            <span class="tag-new tag-green-new">Q2–Q3</span>
                        </div>
                        <div class="card-body-new" style="padding:0;">
                            <div class="mrow-new" style="padding:11px 18px;">
                                <span class="mrow-lbl-new">6-month cash runway</span>
                                <span class="tag-new tag-blue-new">Strategic</span>
                            </div>
                            <div class="mrow-new" style="padding:11px 18px;">
                                <span class="mrow-lbl-new">LTV:CAC ratio &gt; 3.0x</span>
                                <span class="tag-new tag-blue-new">Strategic</span>
                            </div>
                            <div class="mrow-new" style="padding:11px 18px;">
                                <span class="mrow-lbl-new">Regional expansion pilot</span>
                                <span class="tag-new tag-purple-new">Expansion</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="report-footer-new">
                    <div class="footer-note-new">
                        This report is auto-generated from platform data as of {{ now()->format('F j, Y') }}. Metrics are based on active subscriptions, verified fleet activity, and historical AI utilization. For board distribution only.
                    </div>
                    <div class="footer-sig-new">
                        <div class="footer-brand-new">BoardIQ Financial Intelligence</div>
                        <div class="footer-loc-new">Confidential · Nairobi, KE</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>