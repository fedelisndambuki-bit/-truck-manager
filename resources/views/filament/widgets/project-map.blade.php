<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Project Map</x-slot>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

        <div
            wire:ignore
            x-data="{
                sites: @js($this->getMapSites()),
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
                        const center = this.sites.length
                            ? [this.sites[0].lat, this.sites[0].lng]
                            : [-1.286389, 36.817223];

                        this.map = L.map(this.$refs.mapEl).setView(center, this.sites.length ? 6 : 2);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                            maxZoom: 18,
                        }).addTo(this.map);

                        const bounds = [];
                        this.sites.forEach((site) => {
                            const marker = L.marker([site.lat, site.lng]).addTo(this.map);
                            marker.bindPopup(
                                '<strong>' + site.name + '</strong><br>' +
                                'Progress: ' + site.progress + '%<br>' +
                                'Site Manager: ' + site.manager + '<br>' +
                                'Status: ' + site.status
                            );
                            bounds.push([site.lat, site.lng]);
                        });

                        if (bounds.length > 1) {
                            this.map.fitBounds(bounds, { padding: [30, 30] });
                        }
                    });
                }
            }"
            class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10"
        >
            <div x-ref="mapEl" style="height: 380px; width: 100%;"></div>
        </div>

        @if ($this->getMapSites()->isEmpty())
            <p class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">No sites with GPS coordinates yet.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
