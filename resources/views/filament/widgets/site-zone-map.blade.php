<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Site Map</x-slot>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

        <div
            wire:ignore
            x-data="{
                zones: @js($this->getMapZones()),
                map: null,
                init() {
                    const loadLeaflet = () => new Promise((resolve) => {
                        if (window.L) { resolve(); return; }
                        const script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.onload = () => resolve();
                        document.head.appendChild(script);
                    });

                    loadLeaflet().then(() => {
                        const center = this.zones.length
                            ? [this.zones[0].lat, this.zones[0].lng]
                            : [-1.286389, 36.817223];

                        this.map = L.map(this.$refs.mapEl).setView(center, this.zones.length ? 17 : 2);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                            maxZoom: 20,
                        }).addTo(this.map);

                        const bounds = [];
                        this.zones.forEach((zone) => {
                            const marker = L.circleMarker([zone.lat, zone.lng], {
                                radius: 9,
                                color: zone.color,
                                fillColor: zone.color,
                                fillOpacity: 0.85,
                                weight: 2,
                            }).addTo(this.map);
                            marker.bindPopup('<strong>' + zone.name + '</strong><br>' + zone.type);
                            bounds.push([zone.lat, zone.lng]);
                        });

                        if (bounds.length > 1) {
                            this.map.fitBounds(bounds, { padding: [40, 40] });
                        }
                    });
                }
            }"
            class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10"
        >
            <div x-ref="mapEl" style="height: 380px; width: 100%;"></div>
        </div>

        @if ($this->getMapZones()->isEmpty())
            <p class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">No zones with GPS coordinates yet.</p>
        @else
            <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-600 dark:text-gray-400">
                @foreach ($this->getMapZones()->unique('type') as $zone)
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block h-2.5 w-2.5 rounded-full" style="background-color: {{ $zone['color'] }};"></span>
                        {{ $zone['type'] }}
                    </span>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
