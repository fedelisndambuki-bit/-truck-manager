@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
@endpush

<x-filament-panels::page>
    <style>
        /* ── Modern Variables ── */
        :root {
            --primary-rgb: 47, 79, 79;
            --primary: #2f4f4f;
            --primary-light: #3d6666;
            --primary-dark: #1e3333;
            --accent-success: #10b981;
            --accent-warning: #f59e0b;
            --accent-danger: #ef4444;
            --bg-page: #f8fafc;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* ── Full Page Fill ── */
        .fi-main-ctn { padding: 0 !important; max-width: 100% !important; }
        .fi-page { padding: 0 !important; }
        
        .fleet-container {
            width: 100%;
            padding: 24px 32px;
            background-color: var(--bg-page);
            min-height: calc(100vh - 100px);
        }

        /* ── Professional Header ── */
        .page-header-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 40px;
            color: #fff;
            border-radius: 16px;
            margin-bottom: 32px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .page-header-custom::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            pointer-events: none;
        }

        .page-header-content {
            display: flex;
            align-items: center; gap: 20px; position: relative; z-index: 1;
        }

        .page-header-icon {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .page-header-text h1 {
            font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px; color: #fff !important;
        }

        .page-header-text p {
            font-size: 15px; opacity: 0.9; font-weight: 400; color: #e2e8f0 !important;
        }

        .header-pills {
            display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px;
        }

        .header-pill {
            background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); padding: 8px 16px; border-radius: 999px; font-size: 13px; font-weight: 500; backdrop-filter: blur(4px); display: flex; align-items: center; gap: 8px; transition: background 0.2s;
        }

        .header-pill:hover { background: rgba(255, 255, 255, 0.15); }

        .header-pill-badge {
            background: var(--accent-success); width: 8px; height: 8px; border-radius: 50%; box-shadow: 0 0 8px var(--accent-success);
        }

        /* ── Toolbar & Filters ── */
        .toolbar-row {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: #fff; padding: 12px 24px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--card-shadow);
        }

        .filter-tabs {
            display: flex; gap: 8px; background: #f1f5f9; padding: 4px; border-radius: 10px;
        }

        .filter-tab {
            padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #64748b; transition: all 0.2s; display: flex; align-items: center; gap: 8px;
        }

        .filter-tab.active {
            background: #fff; color: var(--primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .filter-tab:hover:not(.active) { color: #1e293b; background: rgba(0,0,0,0.03); }

        /* ── Fleet Grid ── */
        .fleet-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-bottom: 48px;
        }

        .truck-card {
            background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; box-shadow: var(--card-shadow);
        }

        .truck-card:hover {
            transform: translateY(-8px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-light);
        }

        .truck-image-container {
            position: relative; height: 200px; background: #f1f5f9; overflow: hidden;
        }

        .truck-image-container img {
            width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;
        }

        .truck-card:hover .truck-image-container img { transform: scale(1.05); }

        .status-badge {
            position: absolute; top: 16px; right: 16px; padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .status-badge.active { background: rgba(16, 185, 129, 0.9); color: #fff; }
        .status-badge.maintenance { background: rgba(245, 158, 11, 0.9); color: #fff; }
        .status-badge.idle { background: rgba(239, 68, 68, 0.9); color: #fff; }

        .plate-pill {
            position: absolute; bottom: 16px; left: 16px; background: rgba(15, 23, 42, 0.8); color: #fff; padding: 4px 12px; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; letter-spacing: 1px; border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .truck-details { 
            padding: 20px; flex: 1; display: flex; flex-direction: column; background: var(--primary); color: #ffffff;
        }
        
        .brand-label {
            font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em; color: rgba(255, 255, 255, 0.7); margin-bottom: 4px;
        }

        .model-title {
            font-size: 20px; font-weight: 700; color: #ffffff !important; margin-bottom: 16px;
        }

        .specs-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px;
        }

        .spec-item {
            background: rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 10px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .spec-icon { font-size: 12px; color: rgba(255, 255, 255, 0.6); margin-bottom: 4px; }
        .spec-value { font-size: 14px; font-weight: 700; color: #ffffff; }
        .spec-unit { font-size: 10px; color: rgba(255, 255, 255, 0.5); font-weight: 500; }

        .truck-actions {
            margin-top: auto; display: flex; align-items: center; justify-content: space-between; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-details {
            padding: 8px 16px; background: #ffffff; color: var(--primary); border-radius: 8px; font-size: 13px; font-weight: 700; transition: all 0.2s; display: flex; align-items: center; gap: 8px;
        }

        .btn-details:hover { 
            background: #f1f5f9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .last-service-meta {
            font-size: 11px; color: rgba(255, 255, 255, 0.5);
        }

        .last-service-meta strong { 
            color: #ffffff; display: block; font-size: 12px; 
        }

        /* ── Floating Dashboard Button ── */
        .dashboard-footer {
            text-align: center; padding: 40px 0;
        }

        .btn-main-dashboard {
            background: var(--primary); color: #fff; padding: 16px 32px; border-radius: 999px; font-weight: 700; font-size: 15px; box-shadow: 0 10px 20px rgba(47, 79, 79, 0.2); transition: all 0.3s; display: inline-flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 0.5px;
        }

        .btn-main-dashboard:hover {
            background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 15px 30px rgba(47, 79, 79, 0.3);
        }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .fleet-container { padding: 16px; }
            .fleet-grid { grid-template-columns: 1fr; }
            .toolbar-row { flex-direction: column; gap: 16px; align-items: stretch; }
            .filter-tabs { overflow-x: auto; }
        }
    </style>

    <div class="fleet-container" x-data="{ 
        showProfileModal: false, 
        selectedTruck: null,
        modalStyle: '',
        async viewProfile(id, event) {
            const card = event.currentTarget.closest('.truck-card');
            const rect = card.getBoundingClientRect();
            const popoverWidth = 340;
            const gap = 15;
            
            if (window.innerWidth < 768) {
                this.modalStyle = 'position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 340px;';
            } else {
                let leftPos = rect.right + gap;
                let topPos = rect.top;
                if (leftPos + popoverWidth > window.innerWidth) {
                    leftPos = rect.left - popoverWidth - gap;
                }
                this.modalStyle = 'position: fixed; left: ' + leftPos + 'px; top: ' + topPos + 'px; width: ' + popoverWidth + 'px;';
            }
            
            this.selectedTruck = await $wire.getTruckDetails(id);
            this.showProfileModal = true;
        }
    }">
        <!-- Anchored Profile Popover (Tyre Style) -->
        <template x-teleport="body">
            <div x-show="showProfileModal" 
                 class="fixed inset-0 z-[1000]"
                 x-cloak>
                <!-- Transparent Backdrop -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]" @click="showProfileModal = false"></div>
                
                <div :style="modalStyle" 
                     class="z-[1001]"
                     x-show="showProfileModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    
                    <div style="background-color: #2f4f4f !important; color: #ffffff !important; border: 2px solid rgba(255,255,255,0.2) !important;" class="rounded-xl w-full overflow-hidden shadow-2xl pointer-events-auto">
                        <!-- Dark Slate Header -->
                        <div style="background-color: rgba(0,0,0,0.3) !important; border-bottom: 1px solid rgba(255,255,255,0.1) !important;" class="px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-truck" class="w-4 h-4 text-white opacity-80" />
                                <h3 style="color: #ffffff !important;" class="font-bold text-xs tracking-tight" x-text="selectedTruck?.plate_number"></h3>
                            </div>
                            <button @click="showProfileModal = false" class="text-white opacity-70 hover:opacity-100">
                                <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-4 text-left">
                            <div class="grid grid-cols-2 gap-y-4 gap-x-3">
                                <div class="flex flex-col text-left">
                                    <span style="color: rgba(255,255,255,0.5) !important;" class="text-[9px] font-black uppercase tracking-widest text-left">Model</span>
                                    <span style="color: #ffffff !important;" class="text-[12px] font-bold text-left" x-text="selectedTruck?.model"></span>
                                </div>
                                <div class="flex flex-col text-left">
                                    <span style="color: rgba(255,255,255,0.5) !important;" class="text-[9px] font-black uppercase tracking-widest text-left">Brand</span>
                                    <span style="color: #ffffff !important;" class="text-[12px] font-bold text-left" x-text="selectedTruck?.brand"></span>
                                </div>
                                <div class="flex flex-col text-left">
                                    <span style="color: rgba(255,255,255,0.5) !important;" class="text-[9px] font-black uppercase tracking-widest text-left">Mileage</span>
                                    <span style="color: #ffffff !important;" class="text-[12px] font-bold text-left" x-text="Number(selectedTruck?.current_mileage || 0).toLocaleString() + ' KM'"></span>
                                </div>
                                <div class="flex flex-col text-left">
                                    <span style="color: rgba(255,255,255,0.5) !important;" class="text-[9px] font-black uppercase tracking-widest text-left">Driver</span>
                                    <span style="color: #ffffff !important;" class="text-[12px] font-bold truncate text-left" x-text="selectedTruck?.driver?.name || 'Unassigned'"></span>
                                </div>
                                <div style="background-color: rgba(0,0,0,0.2) !important; border: 1px solid rgba(255,255,255,0.1) !important;" class="col-span-2 flex items-center justify-between p-2 rounded-lg">
                                    <span style="color: rgba(255,255,255,0.7) !important;" class="text-[10px] font-bold uppercase">Unit Status</span>
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded uppercase shadow-sm" 
                                          :style="selectedTruck?.status === 'Under Maintenance' ? 'background-color: #f59e0b !important; color: #ffffff !important;' : 'background-color: #10b981 !important; color: #ffffff !important;'"
                                          x-text="selectedTruck?.status"></span>
                                </div>
                            </div>

                            <div style="border-top: 1px solid rgba(255,255,255,0.1) !important;" class="mt-4 pt-3 flex items-center justify-between gap-3">
                                <button @click="showProfileModal = false" 
                                        style="background-color: #dc2626 !important; color: #ffffff !important; display: inline-block !important;"
                                        class="text-[10px] font-black py-2 px-4 rounded-lg hover:opacity-90 transition-all shadow-md uppercase tracking-wider">
                                    Dismiss
                                </button>
                                <a :href="'/admin/trucks/' + selectedTruck?.id + '/edit'" 
                                   style="background-color: #ffffff !important; color: #2f4f4f !important;"
                                   class="text-[10px] font-black py-2 px-3 rounded-lg flex items-center gap-2 hover:bg-slate-100 transition-all shadow-md">
                                    <x-filament::icon icon="heroicon-o-pencil-square" class="w-3.5 h-3.5" />
                                    EDIT PROFILE
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Professional Header -->
        <div class="page-header-custom">
            <div class="page-header-content">
                <div class="page-header-icon">
                    <x-filament::icon icon="heroicon-o-truck" class="w-8 h-8 text-white" />
                </div>
                <div class="page-header-text">
                    <h1>Fleet Command Center</h1>
                    <p>Comprehensive real-time monitoring and asset management for your entire logistics network.</p>
                </div>
            </div>

            <div class="header-pills">
                <div class="header-pill">
                    <span class="header-pill-badge"></span>
                    <span>System Status: <strong>Operational</strong></span>
                </div>
                <div class="header-pill">
                    <i class="fas fa-satellite"></i>
                    <span>GPS Tracking: <strong>Active</strong></span>
                </div>
                <div class="header-pill">
                    <i class="fas fa-shield-check"></i>
                    <span>Compliance: <strong>Verified</strong></span>
                </div>
                <div class="header-pill">
                    <i class="fas fa-layer-group"></i>
                    <span>Total Units: <strong>{{ $this->getTrucks()->count() }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar-row">
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">
                    <i class="fas fa-th-large"></i> All Units
                </button>
                <button class="filter-tab" data-filter="active">
                    <i class="fas fa-play-circle"></i> In Transit
                </button>
                <button class="filter-tab" data-filter="maintenance">
                    <i class="fas fa-tools"></i> Service
                </button>
                <button class="filter-tab" data-filter="idle">
                    <i class="fas fa-pause-circle"></i> Idle
                </button>
            </div>
            
            <div style="font-size: 13px; font-weight: 600; color: #64748b;">
                <i class="fas fa-clock mr-2"></i> Last Updated: {{ now()->format('H:i') }}
            </div>
        </div>

        <!-- Fleet Grid -->
        <div class="fleet-grid">
            @foreach($this->getTrucks() as $truck)
                @php
                    $status = $truck->status;
                    $statusLabel = $status instanceof \App\Enums\TruckStatus ? $status->getLabel() : ($status ?? 'Active');
                    
                    $statusSlug = 'active';
                    if ($status instanceof \App\Enums\TruckStatus) {
                        $statusSlug = match($status) {
                            \App\Enums\TruckStatus::Available => 'active',
                            \App\Enums\TruckStatus::InTransit => 'active',
                            \App\Enums\TruckStatus::UnderMaintenance => 'maintenance',
                        };
                    } else {
                        $statusSlug = strtolower(str_replace(' ', '-', $statusLabel));
                    }
                @endphp

                <div class="truck-card" data-status="{{ $statusSlug }}">
                    <div class="truck-image-container">
                        <img
                            src="{{ $truck->truck_image ? asset('storage/' . $truck->truck_image) : 'https://placehold.co/600x400?text=Truck' }}"
                            alt="{{ $truck->brand }} {{ $truck->model }}"
                        />
                        <span class="status-badge {{ $statusSlug }}">
                            {{ $statusLabel }}
                        </span>
                        <div class="plate-pill">{{ $truck->plate_number }}</div>
                    </div>
                    
                    <div class="truck-details">
                        <span class="brand-label">{{ $truck->brand }}</span>
                        <h3 class="model-title">{{ $truck->model }}</h3>

                        <div class="specs-grid">
                            <div class="spec-item">
                                <div class="spec-icon"><i class="fas fa-tachometer-alt"></i></div>
                                <div class="spec-value">{{ number_format($truck->current_mileage / 1000, 1) }}k</div>
                                <div class="spec-unit">KM</div>
                            </div>
                            <div class="spec-item">
                                <div class="spec-icon"><i class="fas fa-burn"></i></div>
                                <div class="spec-value">{{ $truck->fuel_consumption ?? '0' }}</div>
                                <div class="spec-unit">L/KM</div>
                            </div>
                            <div class="spec-item">
                                <div class="spec-icon"><i class="fas fa-box-open"></i></div>
                                <div class="spec-value">{{ $truck->capacity_tons ?? '0' }}</div>
                                <div class="spec-unit">TONS</div>
                            </div>
                        </div>

                        <div class="truck-actions">
                            <div class="last-service-meta">
                                <span>Last Service</span>
                                <strong>{{ $truck->last_service_date?->format('d M Y') ?? 'Not Recorded' }}</strong>
                            </div>
                            <button @click="viewProfile({{ $truck->id }}, $event)" class="btn-details">
                                View Fleet Profile
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Professional Footer -->
        <div class="dashboard-footer">
            <a href="/admin" class="btn-main-dashboard">
                Access Operations Dashboard <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.filter-tab');
            const cards = document.querySelectorAll('.truck-card');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Update UI
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    const filter = tab.getAttribute('data-filter');

                    // Filter cards with animation
                    cards.forEach(card => {
                        if (filter === 'all' || card.getAttribute('data-status') === filter) {
                            card.style.display = 'flex';
                            setTimeout(() => card.style.opacity = '1', 10);
                        } else {
                            card.style.opacity = '0';
                            setTimeout(() => card.style.display = 'none', 300);
                        }
                    });
                });
            });
        });
    </script>
</x-filament-panels::page>
