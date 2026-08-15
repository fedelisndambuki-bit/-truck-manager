@php
    $items = [
        ['label' => 'Fleet Online', 'value' => '128 Vehicles', 'path' => '<path d="M3 17h13V7H3v10zm13-7h3l3 4v3h-6v-7zM6 20a2 2 0 100-4 2 2 0 000 4zm12 0a2 2 0 100-4 2 2 0 000 4z"/>'],
        ['label' => 'Active Sites', 'value' => '24', 'path' => '<path d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3"/>'],
        ['label' => 'Quarry Operations', 'value' => '8', 'path' => '<path d="M3 20l6-11 4 7 3-5 5 9H3z"/>'],
        ['label' => 'Weighbridges Connected', 'value' => '12', 'path' => '<path d="M12 3v18M5 7l-3 6a3 3 0 006 0l-3-6zm14 0l-3 6a3 3 0 006 0l-3-6zM5 7h14"/>'],
        ['label' => 'Fuel Stations Connected', 'value' => '15', 'path' => '<path d="M3 22V6a2 2 0 012-2h6a2 2 0 012 2v16M3 22h10m5-13h1.5a1.5 1.5 0 011.5 1.5V18a1.5 1.5 0 01-3 0V13l-2-2M5 10h6"/>'],
        ['label' => 'System Status', 'value' => '99.99% Uptime', 'path' => '<path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/>'],
    ];
@endphp

<div class="mt-[22px] flex flex-wrap gap-2.5">
    @foreach ($items as $item)
        <div class="tk-stat flex items-center gap-2 rounded-xl px-3 py-2">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-tanaki-teal">
                {!! $item['path'] !!}
            </svg>
            <div class="leading-tight">
                <div class="text-[9.5px] text-white/50">{{ $item['label'] }}</div>
                <div class="text-[11.5px] font-bold text-white">{{ $item['value'] }}</div>
            </div>
        </div>
    @endforeach
</div>
