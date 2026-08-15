{{--
    A hand-built preview card, not a screenshot — matches the approved
    reference design's full dashboard mockup (sidebar nav, live stats,
    charts, activity feed, utilization bars). The stat tiles are driven by
    the `dashboardPreviewAnimator` Alpine component (resources/js/app.js),
    which nudges one at a time every few seconds so the card reads as
    "live" without being distracting. Everything else is static — it's
    decorative background dressing, not a real dashboard.
--}}
@php
    $navItems = [
        ['label' => 'Dashboard', 'active' => true, 'path' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>'],
        ['label' => 'Fleet', 'path' => '<path d="M3 17h13V7H3v10zm13-7h3l3 4v3h-6v-7zM6 20a2 2 0 100-4 2 2 0 000 4zm12 0a2 2 0 100-4 2 2 0 000 4z"/>'],
        ['label' => 'Construction', 'path' => '<path d="M6 21V9l6-4 6 4v12M9 21v-6h6v6"/>'],
        ['label' => 'Quarry', 'path' => '<path d="M3 20l6-11 4 7 3-5 5 9H3z"/>'],
        ['label' => 'Weighbridge', 'path' => '<path d="M12 3v18M5 7l-3 6a3 3 0 006 0l-3-6zm14 0l-3 6a3 3 0 006 0l-3-6zM5 7h14"/>'],
        ['label' => 'Fuel', 'path' => '<path d="M3 22V6a2 2 0 012-2h6a2 2 0 012 2v16M3 22h10m5-13h1.5a1.5 1.5 0 011.5 1.5V18a1.5 1.5 0 01-3 0V13l-2-2M5 10h6"/>'],
        ['label' => 'Workshops', 'path' => '<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.1-3.1a5 5 0 01-6.6 6.6L5.4 21.4a2 2 0 01-2.8-2.8L11.8 9.9a5 5 0 016.6-6.6l-3.1 3.1z"/>'],
        ['label' => 'Procurement', 'path' => '<path d="M3 4h2l.4 2M7 14h10l3-8H6M7 14L5.4 6M7 14l-1.6 2.4A1 1 0 006.2 18H17M9 21a1 1 0 100-2 1 1 0 000 2zM19 21a1 1 0 100-2 1 1 0 000 2z"/>'],
        ['label' => 'HR & Payroll', 'path' => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c.6-3.4 3.2-5.6 6.5-5.6s5.9 2.2 6.5 5.6"/><circle cx="17.5" cy="8.5" r="2.6"/><path d="M16 14.7c2.6.4 4.4 2.2 4.9 5.3"/>'],
        ['label' => 'Finance', 'path' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.4-1.3-2.5-3-2.5s-3 1-3 2.3c0 3 6 1.4 6 4.4 0 1.3-1.3 2.3-3 2.3s-3-1.1-3-2.5"/>'],
        ['label' => 'Reports', 'path' => '<path d="M4 19V10M10 19V4M16 19v-7M4 19h16"/>'],
        ['label' => 'Settings', 'path' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.04.05a2 2 0 11-2.83 2.83l-.05-.04a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.05.04a2 2 0 11-2.83-2.83l.04-.05a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.04-.05a2 2 0 112.83-2.83l.05.04a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.05-.04a2 2 0 112.83 2.83l-.04.05a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>'],
    ];

    $utilization = [
        ['label' => 'Excavators', 'pct' => 80],
        ['label' => 'Loaders', 'pct' => 65],
        ['label' => 'Haul Trucks', 'pct' => 72],
        ['label' => 'Cranes', 'pct' => 58],
    ];
@endphp

<div class="tk-preview-card mt-[22px] flex rounded-[14px] p-4">
    {{-- Sidebar nav --}}
    <div class="w-[104px] shrink-0 pr-3">
        <div class="mb-4 flex items-center gap-1.5">
            <img src="{{ asset('images/brand/tanaki-hub-icon-dark.png') }}" alt="Tanaki Hub" class="h-4 w-4 shrink-0 object-contain">
            <span class="text-[8.5px] font-extrabold leading-none tracking-wide text-white">TANAKI<span class="block text-tanaki-teal">HUB</span></span>
        </div>

        <nav class="space-y-0.5">
            @foreach ($navItems as $item)
                <div class="flex items-center gap-1.5 rounded-md px-1.5 py-1.5 {{ $item['active'] ?? false ? 'bg-tanaki-teal/15 text-tanaki-teal' : 'text-white/45' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-2.5 w-2.5 shrink-0">
                        {!! $item['path'] !!}
                    </svg>
                    <span class="text-[8px] font-medium leading-none">{{ $item['label'] }}</span>
                </div>
            @endforeach
        </nav>
    </div>

    {{-- Main content --}}
    <div x-data="dashboardPreviewAnimator()" class="min-w-0 flex-1 border-l border-white/10 pl-3.5">
        <div class="mb-3 flex items-center justify-between">
            <span class="text-[14px] font-bold text-white">Fleet Overview</span>
            <span class="rounded-md border border-white/15 px-2 py-[3px] text-[11px] text-white/60">This Week ▾</span>
        </div>

        {{-- Live stat tiles --}}
        <div class="grid grid-cols-4 gap-[8px]">
            <template x-for="stat in [stats.vehicles, stats.trips, stats.fuel, stats.drivers]" :key="stat.label">
                <div class="tk-stat rounded-[10px] px-2.5 py-2.5">
                    <div class="text-[9.5px] leading-tight text-white/70" x-text="stat.label"></div>
                    <div class="tk-stat-val mt-1 text-[16px] font-bold leading-none text-white" x-text="stat.value"></div>
                    <div
                        class="mt-1 text-[10px] font-semibold"
                        :class="stat.color === 'amber' ? 'text-amber-400' : 'text-tanaki-teal'"
                        x-text="'↑ ' + stat.delta.replace('+', '')"
                    ></div>
                </div>
            </template>
        </div>

        {{-- Charts row --}}
        <div class="mt-2 grid grid-cols-3 gap-[8px]">
            <div class="tk-stat rounded-[10px] px-2.5 py-2.5">
                <div class="text-[9.5px] text-white/60">Trips Over Time</div>
                <svg viewBox="0 0 100 34" preserveAspectRatio="none" class="mt-1.5 h-8 w-full">
                    <polyline points="0,28 14,24 28,26 42,16 57,19 71,9 85,13 100,4" fill="none" stroke="#12B5A0" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>

            <div class="tk-stat rounded-[10px] px-2.5 py-2.5">
                <div class="mb-1 text-[9.5px] text-white/60">Vehicle Status</div>
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 36 36" class="h-10 w-10 shrink-0">
                        <g transform="rotate(-90 18 18)">
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#12B5A0" stroke-width="4" stroke-dasharray="62 38" stroke-dashoffset="25" />
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#818cf8" stroke-width="4" stroke-dasharray="17 83" stroke-dashoffset="-37" />
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#fbbf24" stroke-width="4" stroke-dasharray="9 91" stroke-dashoffset="-54" />
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#fb7185" stroke-width="4" stroke-dasharray="11 89" stroke-dashoffset="-63" />
                        </g>
                    </svg>
                    <div class="space-y-0.5 text-[7.5px] leading-tight text-white/70">
                        <div><span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-tanaki-teal"></span>In Use 62%</div>
                        <div><span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-indigo-400"></span>Idle 17%</div>
                        <div><span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-amber-400"></span>Maint. 9%</div>
                        <div><span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-rose-400"></span>Unavail. 11%</div>
                    </div>
                </div>
            </div>

            <div class="tk-stat rounded-[10px] px-2.5 py-2.5">
                <div class="text-[9.5px] text-white/60">Live Weighbridge</div>
                <div class="mt-1 text-[15px] font-bold leading-none text-white">31,689 kg</div>
                <div class="mt-1 text-[8.5px] text-white/50">Truck KDG 890E</div>
                <div class="mt-1 flex items-center gap-1 text-[9px] font-semibold text-tanaki-teal">
                    <span class="h-1.5 w-1.5 rounded-full bg-tanaki-teal"></span> Stable
                </div>
            </div>
        </div>

        {{-- Activity + utilization row --}}
        <div class="mt-2 grid grid-cols-2 gap-[8px]">
            <div class="tk-stat rounded-[10px] px-2.5 py-2.5">
                <div class="mb-1.5 flex items-center justify-between">
                    <span class="text-[9.5px] font-semibold text-white/80">Recent Activity</span>
                    <span class="text-[9px] text-tanaki-teal">View all</span>
                </div>
                <ul class="space-y-1 text-[8.5px] leading-tight text-white/60">
                    <li>KDG 890E entered Weighbridge <span class="text-white/35">· 2m</span></li>
                    <li>Driver John Doe checked in <span class="text-white/35">· 15m</span></li>
                    <li>Excavator EX-07 completed task <span class="text-white/35">· 45m</span></li>
                    <li>Fuel refilled — KDG 123A (120 L) <span class="text-white/35">· 1h</span></li>
                </ul>
            </div>

            <div class="tk-stat rounded-[10px] px-2.5 py-2.5">
                <div class="mb-1.5 text-[9.5px] font-semibold text-white/80">Equipment Utilization</div>
                <div class="space-y-1.5">
                    @foreach ($utilization as $row)
                        <div>
                            <div class="mb-0.5 flex items-center justify-between text-[8px] text-white/60">
                                <span>{{ $row['label'] }}</span>
                                <span>{{ $row['pct'] }}%</span>
                            </div>
                            <div class="h-[3px] rounded-full bg-white/10">
                                <div class="h-[3px] rounded-full bg-tanaki-teal" style="width: {{ $row['pct'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
