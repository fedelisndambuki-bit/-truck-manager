<x-filament-panels::page>
    <div class="fi-page-root" x-data="tyreManagement()">
        <style>
            /* ── Sidebar adjustments for Filament ── */
            .fi-main-ctn { padding: 0 !important; }
            .fi-page { padding: 0 !important; }
            
            *,:before,:after{box-sizing:border-box;margin:0;padding:0}
            
            /* ── Scrollbars ── */
            ::-webkit-scrollbar{width:4px;height:4px}
            ::-webkit-scrollbar-track{background:transparent}
            ::-webkit-scrollbar-thumb{background:#334155;border-radius:4px}

            /* ── Page Header ── */
            .page-header-custom{background-color:#2f4f4f;padding:25px 35px;color:#fff;border-radius:10px;margin-bottom:20px}
            .page-header-content{display:flex;align-items:center;gap:12px}
            .page-header-icon{background:rgba(255,255,255,.15);padding:10px;border-radius:8px;display:flex;align-items:center;justify-content:center}
            .page-header-custom h1{font-size:24px;font-weight:600;margin-bottom:4px}
            .page-header-custom p{font-size:13px;opacity:.85;font-weight:400}

            /* ── Cards & surfaces ── */
            .card{background:#FFFFFF;border:1px solid #E2E8F0;border-radius:14px;color:#1E293B; overflow: hidden;}
            .card-header{padding:14px 18px;border-bottom:1px solid #E2E8F0;display:flex;align-items:center;justify-content:space-between;gap:12px; background: #FFFFFF;}
            .card-body{padding:16px 18px; background: #FFFFFF;}
            .badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
            .badge-blue{background:rgba(14,165,233,.1);color:#0284C7;border:1px solid rgba(14,165,233,.2)}
            .badge-green{background:rgba(34,197,94,.1);color:#16A34A;border:1px solid rgba(34,197,94,.2)}
            .badge-amber{background:rgba(245,158,11,.1);color:#D97706;border:1px solid rgba(245,158,11,.2)}
            .badge-red{background:rgba(239,68,68,.1);color:#DC2626;border:1px solid rgba(239,68,68,.2)}
            .badge-slate{background:rgba(100,116,139,.1);color:#475569;border:1px solid rgba(100,116,139,.2)}

            /* ── Stat cards ── */
            .stat-card{background:#FFFFFF;border:1px solid #E2E8F0;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:8px;transition:border-color .2s ease;color:#1E293B}
            .stat-card:hover{border-color:#CBD5E1}
            .stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
            .stat-number{font-size:28px;font-weight:700;color:#0F172A;font-family:'JetBrains Mono',monospace;letter-spacing:-1px}
            .stat-label{font-size:12px;color:#64748B;font-weight:500}
            .stat-trend{font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px}

            /* ── Tyre inventory cards ── */
            .tyre-card{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px;cursor:grab;transition:all .18s ease;user-select:none;position:relative;overflow:hidden;color:#1E293B}
            .tyre-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:3px 0 0 3px}
            .tyre-card.available::before{background:#4ADE80}
            .tyre-card.retread::before{background:#FCD34D}
            .tyre-card:hover{border-color:#0EA5E9;transform:translateY(-2px);box-shadow:0 8px 24px rgba(14,165,233,.1);cursor:grab}
            .tyre-card.dragging{opacity:.5;cursor:grabbing;transform:rotate(2deg) scale(1.02);border-color:#38BDF8;box-shadow:0 16px 40px rgba(14,165,233,.2)}
            .tyre-serial{font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;color:#0284C7}
            .tyre-brand{font-size:11px;color:#64748B;margin-top:2px}
            .tyre-size{font-family:'JetBrains Mono',monospace;font-size:10px;color:#94A3B8;margin-top:4px}

            /* ── Table ── */
            .data-table{width:100%;border-collapse:collapse;background:#FFFFFF}
            .data-table th{padding:12px 14px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#FFFFFF;border-bottom:1px solid #1E293B;background:darkslategrey}
            .data-table td{padding:12px 14px;font-size:13px;color:#334155;border-bottom:1px solid #F1F5F9;vertical-align:middle}
            .data-table tr:hover td{background:#F8FAFC}

            /* ── Tab system ── */
            .tab-bar{display:flex;gap:2px;background:#F1F5F9;border-radius:10px;padding:4px;border:1px solid #E2E8F0}
            .tab{padding:7px 16px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;color:#64748B;transition:all .18s ease;white-space:nowrap}
            .tab.active{background:#FFFFFF;color:#0F172A;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #E2E8F0}

            /* Progress bar */
            .progress{height:4px;background:#F1F5F9;border-radius:4px;overflow:hidden}
            .progress-bar{height:100%;border-radius:4px;transition:width .6s ease}

            /* Drag ghost */
            .drag-ghost{position:fixed;pointer-events:none;z-index:9999;opacity:.85;transform:rotate(4deg) scale(1.05);background:#FFFFFF;border:2px solid #0EA5E9;border-radius:10px;padding:10px 14px;box-shadow:0 16px 40px rgba(0,0,0,.1);display:none}
            
            .btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:all .18s ease;border:none;white-space:nowrap}
            .btn-primary{background:linear-gradient(135deg,#0EA5E9,#0284C7);color:#fff;}
            .btn-ghost{background:transparent;color:#64748B;border:1px solid #E2E8F0}
            .btn-ghost:hover{background:#F8FAFC;color:#0F172A}
            .input{
                background-color:#FFFFFF !important;
                border:1px solid #E2E8F0 !important;
                border-radius:8px !important;
                padding:8px 12px !important;
                padding-right: 36px !important;
                font-size:13px !important;
                color:#0F172A !important;
                outline:none !important;
                appearance: none !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
                background-position: right 10px center !important;
                background-repeat: no-repeat !important;
                background-size: 1.25em 1.25em !important;
                box-shadow: none !important;
            }
            .input:focus{border-color:#0EA5E9 !important;}

            /* ── Tyre image from public/images ── */
            .tyre-3d {
                position: relative;
                flex-shrink: 0;
                width: 76px;
                height: 98px;
                background-image: url("{{ asset('images/tyre image.png') }}");
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
                filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15));
            }
            .tyre-3d::before, .tyre-3d::after {
                display: none !important;
            }
            .tyre-3d--sm { width: 66px; height: 86px; }
            .tyre-3d--xs { width: 54px; height: 70px; }
            .tyre-list-row {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .tyre-list-meta { flex: 1; min-width: 0; }

            #tyre-drag-ghost {
                position: fixed;
                left: -9999px;
                top: 0;
                z-index: 100000;
                padding: 8px;
                pointer-events: none;
            }
            #tyre-drag-ghost .tyre-3d {
                width: 68px;
                height: 88px;
                filter: drop-shadow(0 12px 24px rgba(0,0,0,0.3));
                transform: rotate(5deg);
            }

            /* ── Interactive 3D axle diagram ── */
            .axle-diagram-panel {
                background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%);
                border: 1px solid #E2E8F0;
                border-radius: 16px;
                overflow: hidden;
            }
            .axle-abstract-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0 40px;
                padding: 22px 28px 16px;
                background: #f8fafc;
                border-bottom: 1px solid #e8edf3;
            }
            .axle-abstract-cell {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .axle-abstract-box {
                width: 100%;
                max-width: 180px;
                padding: 12px 16px;
                border-radius: 10px;
                border: 1px dashed #94a3b8;
                background: #f1f5f9;
                font-size: 17px;
                font-weight: 700;
                color: #2d3748;
            }
            .axle-abstract-line {
                width: 100%;
                height: 1px;
                background: #cbd5e1;
                margin: 12px 0 10px;
            }
            .axle-abstract-cap {
                font-size: 12px;
                font-weight: 500;
                color: #2d3748;
                line-height: 1.35;
            }
            .axle-3d-scene {
                padding: 16px 20px 22px;
                max-width: 920px;
                margin: 0 auto;
            }
            .axle-callout-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 0 8% 4px;
                gap: 16px;
            }
            .axle-callout-3d {
                position: relative;
                padding: 8px 20px 12px;
                border-radius: 999px;
                background: #fff;
                border: 1px solid #cbd5e1;
                font-size: 15px;
                font-weight: 700;
                color: #2d3748;
                box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
                cursor: default;
                transition: box-shadow .2s, border-color .2s, transform .2s;
            }
            .axle-callout-3d::after {
                content: '';
                position: absolute;
                left: 50%;
                bottom: -8px;
                transform: translateX(-50%);
                border-left: 8px solid transparent;
                border-right: 8px solid transparent;
                border-top: 8px solid #fff;
                filter: drop-shadow(0 1px 0 #cbd5e1);
            }
            .axle-callout-3d.is-drop-target {
                border-color: #0ea5e9;
                box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.25);
            }
            .axle-callout-hint {
                display: block;
                font-size: 9px;
                font-weight: 600;
                color: #64748b;
                margin-top: 2px;
                text-transform: uppercase;
                letter-spacing: .04em;
            }
            .axle-tier {
                display: flex;
                justify-content: space-between;
                align-items: center; /* Center-align the beam and tyres horizontally */
                gap: 0;
                padding: 12px 4% 12px;
                min-height: 180px;
                position: relative;
            }
            .axle-tier--dual {
                min-height: 200px;
            }
            .axle-mechanical {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0;
                margin: 0 -45px; /* Overlap only enough to reach the center of the inner tyres */
                padding: 0;
                z-index: 1; /* Behind tyres but visible */
                pointer-events: none;
                filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
            }
            .axle-beam {
                height: 16px;
                flex: 1;
                background: linear-gradient(180deg, 
                    #475569 0%, 
                    #64748b 20%, 
                    #94a3b8 45%, 
                    #64748b 70%, 
                    #334155 100%
                );
                position: relative;
                border-top: 1px solid rgba(255,255,255,0.1);
                border-bottom: 2px solid rgba(0,0,0,0.4);
            }
            /* Beam ends (hubs) - simplified to look like they plug into the tyre center */
            .axle-beam:first-child::before, 
            .axle-beam:last-child::before {
                content: '';
                position: absolute;
                width: 12px;
                height: 12px;
                background: #1e293b;
                top: 50%;
                transform: translateY(-50%);
                border-radius: 50%;
                border: 2px solid #94a3b8;
                box-shadow: 0 0 4px rgba(0,0,0,0.5);
            }
            .axle-beam:first-child::before { left: -6px; }
            .axle-beam:last-child::before { right: -6px; }

            /* ... mechanical texture and other beam details ... */
            .axle-beam::after {
                content: '';
                position: absolute;
                inset: 0;
                background: repeating-linear-gradient(
                    90deg,
                    transparent 0px,
                    transparent 40px,
                    rgba(0,0,0,0.2) 40px,
                    rgba(0,0,0,0.2) 42px,
                    rgba(255,255,255,0.05) 42px,
                    rgba(255,255,255,0.05) 44px
                );
            }
            .axle-diff {
                width: 56px;
                height: 56px;
                margin: 0 -12px;
                border-radius: 50%;
                background: #334155;
                position: relative;
                flex-shrink: 0;
                border: 2px solid #1e293b;
                box-shadow: 
                    inset 0 4px 12px rgba(255,255,255,0.1),
                    inset 0 -4px 12px rgba(0,0,0,0.6),
                    0 10px 20px rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .axle-diff::before {
                content: '';
                width: 38px;
                height: 38px;
                border-radius: 50%;
                background: radial-gradient(circle at 30% 30%, #475569, #1e293b);
                border: 1px solid #0f172a;
            }
            .axle-diff::after {
                content: '• • • • • • • •';
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: rgba(255,255,255,0.2);
                font-size: 14px;
                letter-spacing: 4px;
                transform: rotate(45deg);
                pointer-events: none;
            }
            .axle-joint {
                width: 24px;
                height: 24px;
                border-radius: 6px;
                background: linear-gradient(135deg, #475569, #0f172a);
                margin: 0 4px;
                flex-shrink: 0;
                border: 1px solid #1e293b;
            }

            /* Dual wheel connector beam */
            .axle-dual-connector {
                width: 6px;
                height: 14px;
                margin: 0 -45px; /* Aligns with center and spans to both tyre hubs */
                background: linear-gradient(180deg, #64748b, #334155);
                border: 1px solid #1e293b;
                border-radius: 2px;
                z-index: 1;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            
            .axle-dual-group {
                display: flex;
                gap: 0; /* Gap managed by connector */
                align-items: center;
                position: relative;
                z-index: 2;
            }
            .axle-slot-3d {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-width: 90px;
                min-height: 160px;
                padding: 0;
                border-radius: 12px;
                transition: all 0.15s ease;
                position: relative;
                z-index: 2;
            }

            .axle-foot-3d {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                padding: 12px 8% 4px;
                text-align: center;
                font-size: 12px;
                font-weight: 500;
                color: #2d3748;
                border-top: 1px dashed #e2e8f0;
                margin-top: 8px;
            }
            .axle-tyre-label {
                font-family: ui-monospace, 'JetBrains Mono', monospace;
                font-size: 9px;
                font-weight: 700;
                color: #fff;
                background: #000;
                padding: 3px 7px;
                border-radius: 5px;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .axle-installed-stack {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
                max-width: 100%;
                pointer-events: auto;
            }
            .axle-remove-btn {
                font-size: 10px;
                font-weight: 600;
                padding: 4px 10px;
                border-radius: 6px;
                border: 1px solid #FECACA;
                background: #FEF2F2;
                color: #B91C1C;
                cursor: pointer;
                white-space: nowrap;
                line-height: 1.2;
            }
            .axle-remove-btn:hover {
                background: #FEE2E2;
                border-color: #F87171;
            }
            .axle-slot-empty {
                border: 2px dashed #94A3B8;
                border-radius: 8px;
                background: rgba(255,255,255,0.65);
                color: #64748B;
                font-size: 11px;
                font-weight: 700;
                padding: 6px 8px;
                cursor: pointer;
                min-width: 40px;
                text-align: center;
                backdrop-filter: blur(2px);
            }
            .axle-slot-empty:hover { border-color: #0EA5E9; color: #0EA5E9; background: rgba(240,249,255,0.95); }
            .axle-diagram-placeholder {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 200px;
                color: #64748B;
                font-size: 13px;
                border: 1px dashed #CBD5E1;
                border-radius: 12px;
                margin: 16px;
                background: #F8FAFC;
            }

            /* ── Modal Styles ── */
            .modal-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.5);
                backdrop-filter: blur(4px);
                z-index: 50;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
            }
            .modal-content {
                background: white;
                border-radius: 16px;
                width: 100%;
                max-width: 480px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                overflow: hidden;
            }
            .modal-header {
                padding: 16px 20px;
                border-bottom: 1px solid #1E293B;
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: darkslategrey;
                color: white;
            }
            .modal-body {
                padding: 20px;
            }
            .modal-footer {
                padding: 16px 20px;
                border-top: 1px solid #1E293B;
                background: darkslategrey;
                display: flex;
                justify-content: flex-end;
            }
            .btn-close-modal {
                background: #DC2626;
                color: white;
                padding: 8px 16px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: all .18s ease;
                border: none;
            }
            .btn-close-modal:hover {
                background: #B91C1C;
            }
            .info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }
            .info-item {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .info-label {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #64748B;
                font-weight: 700;
            }
            .info-value {
                font-size: 14px;
                font-weight: 600;
                color: #1E293B;
            }
        </style>

        <div class="fi-page-content">
            <!-- Modal -->
            <div x-show="showModal" class="modal-backdrop" x-cloak @click.self="showModal = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="modal-content" x-show="showModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="tyre-3d tyre-3d--sm" aria-hidden="true" style="width:32px;height:42px"></div>
                            <h3 style="font-size:16px;font-weight:700;color:white" x-text="'Tyre: ' + (selectedTyre ? selectedTyre.serial_number : '')"></h3>
                        </div>
                        <button @click="showModal = false" class="text-white hover:text-gray-200">
                            <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Brand</span>
                                <span class="info-value" x-text="selectedTyre ? selectedTyre.brand : '—'"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Size</span>
                                <span class="info-value" x-text="selectedTyre ? selectedTyre.size : '—'"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Type</span>
                                <span class="info-value">
                                    <span class="badge" :class="selectedTyre && selectedTyre.type === 'Retread' ? 'badge-amber' : 'badge-green'" x-text="selectedTyre ? selectedTyre.type : '—'"></span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <span class="badge" :class="{
                                        'badge-green': selectedTyre && selectedTyre.status === 'Spare',
                                        'badge-blue': selectedTyre && selectedTyre.status === 'Active',
                                        'badge-red': selectedTyre && selectedTyre.status === 'Discarded'
                                    }" x-text="selectedTyre ? selectedTyre.status : '—'"></span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tread Depth</span>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div class="progress" style="width:60px"><div class="progress-bar" :style="selectedTyre ? 'width:' + (selectedTyre.tread_depth/16*100) + '%; background:' + (selectedTyre.tread_depth >= 8 ? '#4ADE80' : (selectedTyre.tread_depth >= 5 ? '#FCD34D' : '#F87171')) : ''"></div></div>
                                    <span class="info-value" :style="selectedTyre ? 'color:' + (selectedTyre.tread_depth >= 8 ? '#16A34A' : (selectedTyre.tread_depth >= 5 ? '#D97706' : '#DC2626')) : ''" x-text="selectedTyre ? selectedTyre.tread_depth + 'mm' : '—'"></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Current Truck</span>
                                <span class="info-value" x-text="selectedTyre && selectedTyre.truck ? selectedTyre.truck.plate_number : '—'"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Category</span>
                                <span class="info-value" x-text="selectedTyre ? selectedTyre.tyre_category || '—' : '—'"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Current Pressure</span>
                                <span class="info-value" x-text="selectedTyre ? (selectedTyre.pressure ? selectedTyre.pressure + ' PSI' : '—') : '—'"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Retread Count</span>
                                <span class="info-value" x-text="selectedTyre ? selectedTyre.retread_count || 0 : '0'"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Warehouse</span>
                                <span class="info-value" x-text="selectedTyre ? selectedTyre.warehouse_location || '—' : '—'"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Supplier</span>
                                <span class="info-value" x-text="selectedTyre ? selectedTyre.supplier || '—' : '—'"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Purchase Date</span>
                                <span class="info-value" x-text="selectedTyre && selectedTyre.purchase_date ? new Date(selectedTyre.purchase_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'"></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Purchase Cost</span>
                                <span class="info-value" x-text="selectedTyre ? (selectedTyre.purchase_cost ? '{{ session('currency', 'KES') }} ' + selectedTyre.purchase_cost : '—') : '—'"></span>
                            </div>
                            <div class="info-item" style="grid-column: 1 / -1;">
                                <span class="info-label">Warranty Details</span>
                                <span class="info-value" style="font-size:12px; font-weight:400" x-text="selectedTyre ? selectedTyre.warranty_details || 'No details provided' : '—'"></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="gap: 8px; justify-content: flex-start; flex-wrap: wrap;">
                        <button @click="$wire.mountAction('inspectTyre', { record: selectedTyre.id })" class="btn btn-primary" style="background: #0EA5E9">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-4 h-4" />
                            Record Inspection
                        </button>
                        <button @click="$wire.mountAction('repairTyre', { record: selectedTyre.id })" class="btn btn-primary" style="background: #F59E0B">
                            <x-filament::icon icon="heroicon-o-wrench" class="w-4 h-4" />
                            Record Repair
                        </button>
                        <div style="flex: 1"></div>
                        <button @click="showModal = false" class="btn btn-ghost">Close</button>
                    </div>
                </div>
            </div>

            <!-- Removal/Status Confirmation Modal -->
            <div x-show="showConfirmModal" class="modal-backdrop" x-cloak @click.self="showConfirmModal = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="modal-content" style="max-width: 440px;" x-show="showConfirmModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
                    <div class="modal-header">
                        <h3 style="font-size:16px;font-weight:700;color:white">Tyre Removal</h3>
                        <button @click="showConfirmModal = false" class="text-white hover:text-gray-200">
                            <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="modal-body" style="text-align: center; padding: 25px 20px;">
                        <div style="background: #F1F5F9; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                            <x-filament::icon icon="heroicon-o-arrow-path" class="w-7 h-7 text-slate-600" />
                        </div>
                        <h4 style="font-size: 17px; font-weight: 700; color: #1E293B; margin-bottom: 8px;">Assess Tyre Condition</h4>
                        <p style="font-size: 13.5px; color: #64748B; line-height: 1.5; margin-bottom: 20px;">What is the condition of this tyre after removal?</p>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <button @click="confirmRemoveTyre()" class="card" style="padding: 16px; border: 2px solid #E2E8F0; transition: all 0.2s; cursor: pointer; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 8px;" onmouseover="this.style.borderColor='#22C55E'; this.style.backgroundColor='#F0FDF4'" onmouseout="this.style.borderColor='#E2E8F0'; this.style.backgroundColor='white'">
                                <x-filament::icon icon="heroicon-o-check-circle" class="w-8 h-8 text-green-600" />
                                <div>
                                    <div style="font-weight: 700; color: #1E293B; font-size: 14px;">Good Condition</div>
                                    <div style="font-size: 11px; color: #64748B;">Move to Spares</div>
                                </div>
                            </button>
                            
                            <button @click="discardSelectedTyre()" class="card" style="padding: 16px; border: 2px solid #E2E8F0; transition: all 0.2s; cursor: pointer; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 8px;" onmouseover="this.style.borderColor='#EF4444'; this.style.backgroundColor='#FEF2F2'" onmouseout="this.style.borderColor='#E2E8F0'; this.style.backgroundColor='white'">
                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-8 h-8 text-red-600" />
                                <div>
                                    <div style="font-weight: 700; color: #1E293B; font-size: 14px;">Bad / Worn Out</div>
                                    <div style="font-size: 11px; color: #64748B;">Move to Scrapped</div>
                                </div>
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer" style="background: #F8FAFC; border-top: 1px solid #F1F5F9; padding: 12px 20px;">
                        <button @click="showConfirmModal = false" class="btn btn-ghost">Cancel Removal</button>
                    </div>
                </div>
            </div>

            <div class="page-header-custom">
                <div class="page-header-content">
                    <div class="page-header-icon">
                        <x-filament::icon icon="heroicon-o-circle-stack" class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h1>Tyre Management</h1>
                        <p>Manage tyre inventory, installations, and axle layouts across your fleet.</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div style="display:flex;align-items:center;justify-content:flex-end;margin-bottom: 24px;">
                <div class="tab-bar">
                    <div class="tab" :class="{ 'active': activeTab === 'inventory' }" @click="activeTab = 'inventory'">Inventory</div>
                    <div class="tab" :class="{ 'active': activeTab === 'layout' }" @click="activeTab = 'layout'">Truck Layout</div>
                    <div class="tab" :class="{ 'active': activeTab === 'maintenance' }" @click="activeTab = 'maintenance'">Maintenance</div>
                    <div class="tab" :class="{ 'active': activeTab === 'history' }" @click="activeTab = 'history'">History</div>
                    <div class="tab" :class="{ 'active': activeTab === 'reports' }" @click="activeTab = 'reports'">Reports</div>
                </div>
            </div>

            <!-- ───── STATS ROW ───── -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px; margin-bottom: 24px;">
                @php
                    $stats = $this->getStats();
                    $statsData = [
                        ['label' => 'Total Tyres', 'value' => $stats['total'], 'icon' => 'heroicon-o-circle-stack', 'bg' => 'rgba(14,165,233,.1)', 'color' => '#0EA5E9', 'badge' => 'Total', 'badgeColor' => 'blue', 'percent' => 100, 'grad' => 'linear-gradient(90deg,#0EA5E9,#38BDF8)'],
                        ['label' => 'Available', 'value' => $stats['available'], 'icon' => 'heroicon-o-check-circle', 'bg' => 'rgba(34,197,94,.1)', 'color' => '#4ADE80', 'badge' => 'Spare', 'badgeColor' => 'green', 'percent' => 46, 'grad' => 'linear-gradient(90deg,#22C55E,#4ADE80)'],
                        ['label' => 'Installed', 'value' => $stats['installed'], 'icon' => 'heroicon-o-truck', 'bg' => 'rgba(14,165,233,.1)', 'color' => '#38BDF8', 'badge' => 'Active', 'badgeColor' => 'blue', 'percent' => 37, 'grad' => 'linear-gradient(90deg,#0EA5E9,#38BDF8)'],
                        ['label' => 'Worn / Scrapped', 'value' => $stats['scrapped'], 'icon' => 'heroicon-o-trash', 'bg' => 'rgba(239,68,68,.1)', 'color' => '#DC2626', 'badge' => 'Scrapped', 'badgeColor' => 'red', 'percent' => 10, 'grad' => 'linear-gradient(90deg,#EF4444,#F87171)'],
                    ];
                @endphp
                @foreach($statsData as $stat)
                    <div class="stat-card">
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div class="stat-icon" style="background: {{ $stat['bg'] }}">
                                <x-filament::icon :icon="$stat['icon']" class="w-5 h-5" style="color: {{ $stat['color'] }}" />
                            </div>
                            <span class="badge badge-{{ $stat['badgeColor'] }}">{{ $stat['badge'] }}</span>
                        </div>
                        <div class="stat-number">{{ $stat['value'] }}</div>
                        <div class="stat-label">{{ $stat['label'] }}</div>
                        <div class="progress"><div class="progress-bar" style="width: {{ $stat['percent'] }}%; background: {{ $stat['grad'] }}"></div></div>
                    </div>
                @endforeach
            </div>

            <!-- ───── ALERTS SECTION ───── -->
            @php
                $lowTreadTyres = $this->getTyreData()->filter(fn($t) => $t->status === 'Active' && $t->tread_depth < 4);
                $lowPressureTyres = $this->getTyreData()->filter(fn($t) => $t->status === 'Active' && $t->pressure < 100);
            @endphp
            @if($lowTreadTyres->isNotEmpty() || $lowPressureTyres->isNotEmpty())
            <div class="card" style="margin-bottom: 24px; border-left: 4px solid #DC2626; background: #FFF1F2;">
                <div class="card-body" style="background: transparent; display: flex; align-items: center; gap: 16px; padding: 12px 20px;">
                    <div style="background: #FEE2E2; padding: 8px; border-radius: 50%;">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 text-red-600" />
                    </div>
                    <div style="flex: 1">
                        <h3 style="font-size: 14px; font-weight: 700; color: #991B1B;">Maintenance Alerts Detected</h3>
                        <div style="display: flex; gap: 20px; margin-top: 4px;">
                            @if($lowTreadTyres->count() > 0)
                                <div style="font-size: 12px; color: #B91C1C; font-weight: 500;">
                                    <strong>{{ $lowTreadTyres->count() }}</strong> tyres have critical tread depth (< 4mm)
                                </div>
                            @endif
                            @if($lowPressureTyres->count() > 0)
                                <div style="font-size: 12px; color: #B91C1C; font-weight: 500;">
                                    <strong>{{ $lowPressureTyres->count() }}</strong> tyres reported low pressure
                                </div>
                            @endif
                        </div>
                    </div>
                    <button class="btn btn-ghost" style="border-color: #FECACA; color: #B91C1C;" @click="activeTab = 'inventory'">
                        Review in Inventory
                    </button>
                </div>
            </div>
            @endif

            <!-- ───── INVENTORY TAB ───── -->
            <div x-show="activeTab === 'inventory'" class="animate-slide-in">
                <div class="card">
                    <div class="card-header" style="background: darkslategrey; color: white;">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center">
                                <svg width="15" height="15" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
                            </div>
                            <div>
                                <div style="font-size:13px;font-weight:600;">Tyre Inventory</div>
                                <div style="font-size:11px;color:rgba(255,255,255,.7)" x-text="tyres.length + ' tyres across all statuses'"></div>
                            </div>
                        </div>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Serial Number</th>
                                    <th>Brand</th>
                                    <th>Size</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Purchase Cost</th>
                                    <th>Truck</th>
                                    <th>Position</th>
                                    <th>Tread Depth</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="tyre in tyres">
                                    <tr>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <div class="tyre-3d tyre-3d--xs" aria-hidden="true"></div>
                                                <span style="font-family:'JetBrains Mono',monospace;color:#0284C7;font-weight:600;font-size:12px" x-text="tyre.serial_number"></span>
                                            </div>
                                        </td>
                                        <td style="font-weight:500" x-text="tyre.brand"></td>
                                        <td><span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:#64748B" x-text="tyre.size"></span></td>
                                        <td style="color:#64748B;font-size:12px" x-text="tyre.type"></td>
                                        <td>
                                            <span class="badge" :class="{
                                                'badge-green': tyre.status === 'Spare',
                                                'badge-blue': tyre.status === 'Active',
                                                'badge-amber': tyre.type === 'Retread',
                                                'badge-red': tyre.status === 'Discarded'
                                            }" x-text="tyre.status"></span>
                                        </td>
                                        <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#1E293B">
                                            <span x-text="tyre.purchase_cost ? '{{ session('currency', 'KES') }} ' + Number(tyre.purchase_cost).toLocaleString() : '—'"></span>
                                        </td>
                                        <td style="color:#64748B;font-size:12px" x-text="tyre.truck ? tyre.truck.plate_number : '—'"></td>
                                        <td><span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#64748B" x-text="tyre.position || '—'"></span></td>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <div class="progress" style="width:60px"><div class="progress-bar" :style="'width:' + (tyre.tread_depth ? (tyre.tread_depth/16*100) : 0) + '%; background:' + (tyre.tread_depth >= 8 ? '#4ADE80' : (tyre.tread_depth >= 5 ? '#FCD34D' : '#F87171'))"></div></div>
                                                <span style="font-family:'JetBrains Mono',monospace;font-size:11px" :style="'color:' + (tyre.tread_depth >= 8 ? '#4ADE80' : (tyre.tread_depth >= 5 ? '#FCD34D' : '#F87171'))" x-text="(tyre.tread_depth || 0) + 'mm'"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:4px">
                                                <button 
                                                    class="btn btn-ghost" 
                                                    style="padding:4px 8px;font-size:11px"
                                                    @click="$wire.mountAction('editTyre', { record: tyre.id })"
                                                >
                                                    Edit
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ───── TRUCK LAYOUT TAB ───── -->
            <div x-show="activeTab === 'layout'" class="animate-slide-in">
                <div style="display:grid;grid-template-columns:minmax(260px,280px) 1fr minmax(260px,280px);gap:16px;align-items:start">
                    <!-- Left: Available Tyres -->
                    <div class="card">
                        <div class="card-header" style="background: darkslategrey; color: white; padding: 12px 16px;">
                            <div>
                                <div style="font-size:13px;font-weight:600;">Available Spares</div>
                                <div style="font-size:10px;color:rgba(255,255,255,.7)">Drag to truck slots</div>
                            </div>
                        </div>
                        <div style="padding:10px;display:flex;flex-direction:column;gap:8px;max-height:600px;overflow-y:auto">
                            <template x-for="tyre in availableTyres" :key="tyre.id">
                                <div
                                    class="tyre-card available"
                                    draggable="true"
                                    @dragstart="dragStart($event, tyre)"
                                    @dragend="dragEnd()"
                                >
                                    <div class="tyre-list-row">
                                        <div class="tyre-3d tyre-3d--xs" style="width:34px;height:44px" aria-hidden="true"></div>
                                        <div class="tyre-list-meta">
                                            <div class="tyre-serial" x-text="tyre.serial_number"></div>
                                            <div class="tyre-brand" style="font-size:10px" x-text="tyre.brand"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="availableTyres.length === 0">
                                <div style="padding:20px;text-align:center;color:#94A3B8;font-size:12px;border:1px dashed #E2E8F0;border-radius:8px">No available spares</div>
                            </template>
                        </div>
                    </div>

                    <!-- Middle: Truck diagram (rest of code stays same) -->
                    <div>
                        <div class="card" style="margin-bottom:14px">
                            <div class="card-header" style="background: darkslategrey; color: white; padding: 10px 18px;">
                                <div style="font-size:13px;font-weight:600;">Vehicle Selection & Axle Layout</div>
                            </div>
                            <div class="card-body" style="padding: 12px 18px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <label style="font-size:12px; font-weight:600; color:#64748B; white-space:nowrap">Select Truck:</label>
                                    <select class="input w-full" x-model="selectedTruckId" style="border-color: #E2E8F0; font-weight: 500;">
                                        <option value="">-- Choose a Truck --</option>
                                        <template x-for="truck in trucks">
                                            <option :value="truck.id" x-text="truck.plate_number + ' (' + (truck.axle_config || '2-axle') + ')'"></option>
                                        </template>
                                    </select>
                                    <button 
                                        class="btn btn-ghost" 
                                        style="padding: 8px 12px;"
                                        @click="$wire.mountAction('configure_truck', { truck_id: selectedTruckId })"
                                    >
                                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body" style="padding: 0;">
                                <template x-if="!selectedTruckId">
                                    <div class="axle-diagram-placeholder">Select a truck to view layout</div>
                                </template>

                                <div class="axle-diagram-panel" x-show="selectedTruckId" x-cloak style="padding-bottom: 40px;">
                                    <!-- Dynamic Axle Scene -->
                                    <div class="axle-3d-scene" style="display: flex; flex-direction: column; gap: 40px; padding-top: 40px;">
                                        <template x-for="axleIndex in getAxleCount()" :key="axleIndex">
                                            <div class="axle-group">
                                                <div style="text-align: center; margin-bottom: 12px;">
                                                    <span class="badge badge-slate" x-text="'Axle ' + axleIndex + (axleIndex === 1 ? ' - Steering' : ' - Drive')"></span>
                                                </div>
                                                <div class="axle-tier" :class="{ 'axle-tier--dual': getTyresPerSide(axleIndex) > 1 }">
                                                    <!-- Left -->
                                                    <div class="axle-dual-group">
                                                        <template x-for="tIndex in getTyresPerSide(axleIndex)" :key="tIndex">
                                                            <div class="axle-slot-3d" x-data="{ pos: 'L' + axleIndex + (getTyresPerSide(axleIndex) > 1 ? (tIndex === 1 ? 'i' : 'o') : '') }" :class="{ 'is-drop-target': dragOverPos === pos }" @dragover.prevent="dragOverPos = pos" @dragleave="dragLeaveSlot($event, pos)" @drop.prevent="dragOverPos = null; dropTyre($event, pos)">
                                                                <template x-if="getTyreAt(pos)">
                                                                    <div class="axle-installed-stack">
                                                                        <div class="tyre-3d tyre-3d--sm" aria-hidden="true"></div>
                                                                        <button type="button" class="axle-tyre-label" @click="showTyreDetail(getTyreAt(pos))" x-text="getTyreAt(pos).serial_number"></button>
                                                                        <button type="button" class="axle-remove-btn" @click.stop="removeTyre(getTyreAt(pos).id)">Remove</button>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!getTyreAt(pos)">
                                                                    <div class="axle-installed-stack">
                                                                        <div class="tyre-3d tyre-3d--sm" style="opacity:.15;filter:grayscale(1)" aria-hidden="true"></div>
                                                                        <span class="axle-slot-empty" x-text="pos"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <!-- Beam -->
                                                    <div class="axle-mechanical"><div class="axle-beam"></div><template x-if="axleIndex > 1"><div class="axle-diff"></div></template><template x-if="axleIndex === 1"><div class="axle-joint"></div></template><div class="axle-beam"></div></div>
                                                    <!-- Right -->
                                                    <div class="axle-dual-group">
                                                        <template x-for="tIndex in getTyresPerSide(axleIndex)" :key="tIndex">
                                                            <div class="axle-slot-3d" x-data="{ pos: 'R' + axleIndex + (getTyresPerSide(axleIndex) > 1 ? (tIndex === 1 ? 'i' : 'o') : '') }" :class="{ 'is-drop-target': dragOverPos === pos }" @dragover.prevent="dragOverPos = pos" @dragleave="dragLeaveSlot($event, pos)" @drop.prevent="dragOverPos = null; dropTyre($event, pos)">
                                                                <template x-if="getTyreAt(pos)">
                                                                    <div class="axle-installed-stack">
                                                                        <div class="tyre-3d tyre-3d--sm" aria-hidden="true"></div>
                                                                        <button type="button" class="axle-tyre-label" @click="showTyreDetail(getTyreAt(pos))" x-text="getTyreAt(pos).serial_number"></button>
                                                                        <button type="button" class="axle-remove-btn" @click.stop="removeTyre(getTyreAt(pos).id)">Remove</button>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!getTyreAt(pos)">
                                                                    <div class="axle-installed-stack">
                                                                        <div class="tyre-3d tyre-3d--sm" style="opacity:.15;filter:grayscale(1)" aria-hidden="true"></div>
                                                                        <span class="axle-slot-empty" x-text="pos"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Worn Out / Bad Tyres -->
                    <div class="card">
                        <div class="card-header" style="background: #991B1B; color: white; padding: 12px 16px;">
                            <div>
                                <div style="font-size:13px;font-weight:600;">Worn Out / Bad</div>
                                <div style="font-size:10px;color:rgba(255,255,255,.7)">Tyres marked for disposal</div>
                            </div>
                        </div>
                        <div style="padding:10px;display:flex;flex-direction:column;gap:8px;max-height:600px;overflow-y:auto">
                            <template x-for="tyre in discardedTyres" :key="tyre.id">
                                <div class="tyre-card" style="border-left: 3px solid #EF4444; background: #FFF1F2;">
                                    <div class="tyre-list-row">
                                        <div class="tyre-3d tyre-3d--xs" style="width:34px;height:44px;filter:grayscale(1);opacity:0.6" aria-hidden="true"></div>
                                        <div class="tyre-list-meta">
                                            <div class="tyre-serial" style="color:#991B1B" x-text="tyre.serial_number"></div>
                                            <div class="tyre-brand" style="font-size:10px" x-text="tyre.brand"></div>
                                        </div>
                                        <button @click="reinstallTyre(tyre.id)" class="btn btn-ghost" style="padding:4px 6px; font-size:9px; border-color:#FECACA; background:white" title="Restore to Available">
                                            Restore
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="discardedTyres.length === 0">
                                <div style="padding:20px;text-align:center;color:#94A3B8;font-size:12px;border:1px dashed #E2E8F0;border-radius:8px">No worn out tyres</div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ───── MAINTENANCE TAB ───── -->
            <div x-show="activeTab === 'maintenance'" class="animate-slide-in">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <!-- Inspections -->
                    <div class="card">
                        <div class="card-header" style="background: darkslategrey; color: white;">
                            <div style="font-size:13px;font-weight:600;">Recent Inspections</div>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tyre</th>
                                        <th>Date</th>
                                        <th>Tread</th>
                                        <th>PSI</th>
                                        <th>Technician</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->getInspections() as $insp)
                                    <tr>
                                        <td><span style="font-family:'JetBrains Mono',monospace;color:#0284C7;font-size:12px">{{ $insp->tyre->serial_number }}</span></td>
                                        <td style="font-size:12px;color:#64748B">{{ $insp->inspection_date->format('d M Y') }}</td>
                                        <td>{{ $insp->tread_depth }}mm</td>
                                        <td>{{ $insp->pressure }} PSI</td>
                                        <td style="font-size:12px">{{ $insp->technician->name ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                    @if($this->getInspections()->isEmpty())
                                    <tr><td colspan="5" style="text-align:center;padding:20px;color:#94A3B8">No inspections recorded</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Repairs -->
                    <div class="card">
                        <div class="card-header" style="background: darkslategrey; color: white;">
                            <div style="font-size:13px;font-weight:600;">Recent Repairs & Retreads</div>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tyre</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Cost</th>
                                        <th>Vendor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->getRepairs() as $rep)
                                    <tr>
                                        <td><span style="font-family:'JetBrains Mono',monospace;color:#0284C7;font-size:12px">{{ $rep->tyre->serial_number }}</span></td>
                                        <td style="font-size:12px;color:#64748B">{{ $rep->repair_date->format('d M Y') }}</td>
                                        <td><span class="badge {{ $rep->repair_type === 'Retread' ? 'badge-amber' : 'badge-slate' }}">{{ $rep->repair_type }}</span></td>
                                        <td style="font-family:'JetBrains Mono',monospace">{{ session('currency', 'KES') }} {{ number_format($rep->cost, 2) }}</td>
                                        <td style="font-size:12px">{{ $rep->vendor ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                    @if($this->getRepairs()->isEmpty())
                                    <tr><td colspan="5" style="text-align:center;padding:20px;color:#94A3B8">No repairs recorded</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ───── HISTORY TAB ───── -->
            <div x-show="activeTab === 'history'" class="animate-slide-in">
                <div class="card">
                    <div class="card-header" style="background: darkslategrey; color: white;">
                        <div style="font-size:13px;font-weight:600;">Comprehensive Tyre Lifecycle History</div>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tyre Serial</th>
                                    <th>Brand</th>
                                    <th>Current/Last Truck</th>
                                    <th>Category</th>
                                    <th>Fitment Date</th>
                                    <th>Removal Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="tyre in tyres">
                                    <tr>
                                        <td><span style="font-family:'JetBrains Mono',monospace;color:#0284C7;font-size:12px" x-text="tyre.serial_number"></span></td>
                                        <td x-text="tyre.brand"></td>
                                        <td><span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#64748B" x-text="tyre.truck ? tyre.truck.plate_number : '—'"></span></td>
                                        <td><span class="badge badge-slate" x-text="tyre.tyre_category || '—'"></span></td>
                                        <td style="color:#64748B;font-size:12px" x-text="tyre.installation_date ? new Date(tyre.installation_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'"></td>
                                        <td style="color:#64748B;font-size:12px" x-text="tyre.removal_date ? new Date(tyre.removal_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'"></td>
                                        <td>
                                            <span class="badge" :class="{
                                                'badge-green': tyre.status === 'Spare',
                                                'badge-blue': tyre.status === 'Active',
                                                'badge-red': tyre.status === 'Discarded',
                                                'badge-amber': tyre.status === 'Under Repair'
                                            }" x-text="tyre.status"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ───── REPORTS TAB ───── -->
            <div x-show="activeTab === 'reports'" class="animate-slide-in">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="card">
                        <div class="card-header" style="background: darkslategrey; color: white;"><div style="font-size:13px;font-weight:600;">Tyres by Brand</div></div>
                        <div class="card-body">
                            @foreach($this->getBrandReport() as $report)
                            <div style="margin-bottom: 12px;">
                                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                                    <span style="font-size:12px;font-weight:500;color:#1E293B">{{ $report->brand }}</span>
                                    <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#0EA5E9">{{ $report->count }}</span>
                                </div>
                                <div class="progress"><div class="progress-bar" style="width: {{ ($report->count / max(1, $this->getStats()['total'])) * 100 }}%; background:#0EA5E9"></div></div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" style="background: darkslategrey; color: white;"><div style="font-size:13px;font-weight:600;">Tyre Status Overview</div></div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                <div style="padding:14px;background:#F8FAFC;border-radius:10px;border:1px solid #E2E8F0;text-align:center">
                                    <div style="font-family:'JetBrains Mono',monospace;font-size:24px;font-weight:700;color:#16A34A">{{ $this->getStats()['available'] }}</div>
                                    <div style="font-size:11px;color:#64748B;margin-top:4px">Available</div>
                                </div>
                                <div style="padding:14px;background:#F8FAFC;border-radius:10px;border:1px solid #E2E8F0;text-align:center">
                                    <div style="font-family:'JetBrains Mono',monospace;font-size:24px;font-weight:700;color:#0284C7">{{ $this->getStats()['installed'] }}</div>
                                    <div style="font-size:11px;color:#64748B;margin-top:4px">Installed</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="grid-column: 1 / -1;">
                        <div class="card-header" style="background: #1E293B; color: white;">
                            <div style="display:flex;align-items:center;gap:10px">
                                <x-filament::icon icon="heroicon-o-sparkles" class="w-5 h-5 text-amber-400" />
                                <div style="font-size:13px;font-weight:600;">AI Smart Insights & Recommendations</div>
                            </div>
                        </div>
                        <div class="card-body" style="line-height: 1.6; color: #334155; font-size: 13.5px; background: #F8FAFC;">
                            @php
                                $insights = $this->getSmartInsights();
                            @endphp
                            <div style="white-space: pre-line;">
                                {{ $insights }}
                            </div>
                        </div>
                    </div>

                    <div class="card" style="grid-column: 1 / -1;">
                        <div style="overflow-x:auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Truck</th>
                                        <th>Model</th>
                                        <th>Total Slots</th>
                                        <th>Installed</th>
                                        <th>Empty</th>
                                        <th>Fill Rate</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->getTruckTyreReport() as $row)
                                    <tr>
                                        <td><span style="font-family:'JetBrains Mono',monospace;color:#0284C7;font-size:12px">{{ $row['plate_number'] }}</span></td>
                                        <td style="color:#64748B">{{ $row['model'] }}</td>
                                        <td style="font-family:'JetBrains Mono',monospace">{{ $row['total_slots'] }}</td>
                                        <td style="font-family:'JetBrains Mono',monospace;color:#16A34A">{{ $row['installed'] }}</td>
                                        <td style="font-family:'JetBrains Mono',monospace;color:#64748B">{{ $row['empty'] }}</td>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <div class="progress" style="width:80px"><div class="progress-bar" style="width:{{ $row['fill_rate'] }}%; background:{{ $row['fill_rate'] == 100 ? '#16A34A' : ($row['fill_rate'] >= 70 ? '#0284C7' : '#D97706') }}"></div></div>
                                                <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#64748B">{{ round($row['fill_rate']) }}%</span>
                                            </div>
                                        </td>
                                        <td style="color:#64748B;font-size:12px">{{ $row['last_updated'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tyre-drag-ghost" aria-hidden="true">
                <div class="tyre-3d"></div>
            </div>
        </div>

        <script>
            function tyreManagement() {
                return {
                    activeTab: 'inventory',
                    tyres: @js($this->getTyreData()),
                    trucks: @js($this->getTrucks()),
                    selectedTruckId: '',
                    draggedTyre: null,
                    dragOverPos: null,
                    dragOverGroup: null,
                    
                    showModal: false,
                    selectedTyre: null,
                    
                    showConfirmModal: false,
                    tyreToRemove: null,

                    get currentTruck() {
                        return this.trucks.find(t => t.id == this.selectedTruckId);
                    },

                    getAxleCount() {
                        if (!this.currentTruck) return 0;
                        const config = this.currentTruck.axle_config || '2-axle';
                        return parseInt(config.split('-')[0]) || 2;
                    },

                    getTyresPerSide(axleIndex) {
                        if (!this.currentTruck) return 1;
                        const totalTyres = this.currentTruck.tyre_count || 6;
                        const axleCount = this.getAxleCount();
                        
                        // Logic: Axle 1 is always steering (2 tyres total, 1 per side)
                        if (axleIndex === 1) return 1;
                        
                        // Logic: Distribute remaining tyres across other axles
                        const remainingTyres = totalTyres - 2;
                        const driveAxles = axleCount - 1;
                        if (driveAxles <= 0) return 1;
                        const tyresPerDriveAxle = remainingTyres / driveAxles;
                        
                        return tyresPerDriveAxle / 2 >= 2 ? 2 : 1;
                    },

                    get availableTyres() {
                        return this.tyres.filter(t => t.status === 'Spare');
                    },

                    get discardedTyres() {
                        return this.tyres.filter(t => t.status === 'Discarded');
                    },

                    getTyreAt(position) {
                        if (!this.selectedTruckId) return null;
                        return this.tyres.find(t => t.truck_id == this.selectedTruckId && t.position === position);
                    },

                    showTyreDetail(tyre) {
                        this.selectedTyre = tyre;
                        this.showModal = true;
                    },

                    dragStart(event, tyre) {
                        this.draggedTyre = tyre;
                        this.dragOverPos = null;
                        this.dragOverGroup = null;
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', String(tyre.id));
                        const ghost = document.getElementById('tyre-drag-ghost');
                        if (ghost) {
                            event.dataTransfer.setDragImage(ghost, 36, 48);
                        }
                    },

                    dragEnd() {
                        this.dragOverPos = null;
                        this.dragOverGroup = null;
                    },

                    dragLeaveSlot(event, pos) {
                        const el = event.currentTarget;
                        if (event.relatedTarget && el.contains(event.relatedTarget)) {
                            return;
                        }
                        if (this.dragOverPos === pos) {
                            this.dragOverPos = null;
                        }
                    },

                    async refreshTyres() {
                        this.tyres = await this.$wire.getTyresForLayout();
                    },

                    async removeTyre(tyreId) {
                        this.tyreToRemove = tyreId;
                        this.showConfirmModal = true;
                    },

                    async confirmRemoveTyre() {
                        if (!this.tyreToRemove) return;
                        
                        const tyreId = this.tyreToRemove;
                        this.showConfirmModal = false;
                        this.tyreToRemove = null;

                        // Optimistic Update: Immediately hide from truck and show in spares
                        const originalTyres = JSON.parse(JSON.stringify(this.tyres));
                        const tyre = this.tyres.find(t => t.id === tyreId);
                        if (tyre) {
                            tyre.truck_id = null;
                            tyre.position = null;
                            tyre.status = 'Spare';
                        }

                        try {
                            await this.$wire.removeTyreFromTruck(tyreId);
                            this.refreshTyres();
                            
                            new FilamentNotification()
                                .title('Tyre removed')
                                .body('The tyre is back in available spares.')
                                .success()
                                .send();
                        } catch (e) {
                            // Revert on failure
                            this.tyres = originalTyres;
                            new FilamentNotification()
                                .title('Error')
                                .body('Could not remove tyre. Please try again.')
                                .danger()
                                .send();
                        }
                    },

                    async discardSelectedTyre() {
                        if (!this.tyreToRemove) return;
                        
                        const tyreId = this.tyreToRemove;
                        this.showConfirmModal = false;
                        this.tyreToRemove = null;

                        // Optimistic Update
                        const originalTyres = JSON.parse(JSON.stringify(this.tyres));
                        const tyre = this.tyres.find(t => t.id === tyreId);
                        if (tyre) {
                            tyre.truck_id = null;
                            tyre.position = null;
                            tyre.status = 'Discarded';
                        }

                        try {
                            await this.$wire.discardTyre(tyreId);
                            this.refreshTyres();
                            
                            new FilamentNotification()
                                .title('Tyre Discarded')
                                .body('The tyre has been moved to the worn out list.')
                                .warning()
                                .send();
                        } catch (e) {
                            this.tyres = originalTyres;
                            new FilamentNotification()
                                .title('Error')
                                .body('Could not discard tyre.')
                                .danger()
                                .send();
                        }
                    },

                    async reinstallTyre(tyreId) {
                        const originalTyres = JSON.parse(JSON.stringify(this.tyres));
                        const tyre = this.tyres.find(t => t.id === tyreId);
                        if (tyre) {
                            tyre.status = 'Spare';
                        }

                        try {
                            await this.$wire.reinstallTyre(tyreId);
                            this.refreshTyres();
                            new FilamentNotification()
                                .title('Tyre Restored')
                                .body('Tyre moved back to available spares.')
                                .success()
                                .send();
                        } catch (e) {
                            this.tyres = originalTyres;
                        }
                    },

                    async dropTyre(event, position) {
                        if (!this.draggedTyre || !this.selectedTruckId) {
                            new FilamentNotification()
                                .title('Error')
                                .body('Drag a spare tyre from the list, and select a truck first.')
                                .danger()
                                .send();
                            return;
                        }

                        const tyreId = this.draggedTyre.id;
                        const truckId = this.selectedTruckId;
                        const originalTyres = JSON.parse(JSON.stringify(this.tyres));

                        // Optimistic Update: Move tyre to slot immediately
                        const tyre = this.tyres.find(t => t.id === tyreId);
                        if (tyre) {
                            tyre.truck_id = truckId;
                            tyre.position = position;
                            tyre.status = 'Active';
                        }

                        try {
                            await this.$wire.installTyre(tyreId, truckId, position);
                            this.refreshTyres(); // Sync in background

                            new FilamentNotification()
                                .title('Tyre installed')
                                .body(`${this.draggedTyre.serial_number} installed at ${position}`)
                                .success()
                                .send();
                        } catch (e) {
                            // Revert on failure
                            this.tyres = originalTyres;
                            new FilamentNotification()
                                .title('Error')
                                .body('Could not install tyre. Please try again.')
                                .danger()
                                .send();
                        }

                        this.draggedTyre = null;
                    }
                }
            }
        </script>
        <script>
            window.addEventListener('trucks-refreshed', async () => {
                const el = document.querySelector('[x-data^="tyreManagement"]');
                if (el && el.__x) {
                    const component = el.__x.$data;
                    component.trucks = await component.$wire.getTrucksForLayout();
                } else {
                    window.location.reload();
                }
            });
        </script>
    </div>
</x-filament-panels::page>
