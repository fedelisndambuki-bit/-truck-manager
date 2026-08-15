// This ensures Alpine.js (which Filament uses) is initialized if you add custom logic
document.addEventListener('alpine:init', () => {
    console.log('Alpine and Filament scripts are ready.');

    /**
     * Drives the small, tasteful "the software is alive" updates on the
     * Fleet Overview live preview card: one figure nudges every few
     * seconds, never all four at once, never a jarring jump. Respects the
     * user's motion preference.
     */
    Alpine.data('dashboardPreviewAnimator', () => ({
        reducedMotion: false,

        stats: {
            vehicles: { label: 'Total Vehicles', value: 128, delta: '+12%', color: 'teal' },
            trips: { label: "Today's Trips", value: 342, delta: '+8%', color: 'teal' },
            fuel: { label: 'Fuel Consumption', value: '3,620 L', delta: '+5%', color: 'amber' },
            drivers: { label: 'Active Drivers', value: 96, delta: '+7%', color: 'teal' },
        },

        init() {
            this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (this.reducedMotion) {
                return;
            }

            setInterval(() => this.tickVehicles(), 5000);
            setInterval(() => this.tickTrips(), 6500);
            setInterval(() => this.tickFuel(), 8000);
            setInterval(() => this.tickDrivers(), 7200);
        },

        tickVehicles() {
            const delta = Math.random() > 0.5 ? 1 : -1;
            this.stats.vehicles.value = Math.max(120, this.stats.vehicles.value + delta);
        },

        tickTrips() {
            this.stats.trips.value += Math.floor(Math.random() * 3);
        },

        tickFuel() {
            const current = parseInt(this.stats.fuel.value.replace(/[^\d]/g, ''), 10);
            const delta = Math.floor(Math.random() * 60) - 20;
            this.stats.fuel.value = (current + delta).toLocaleString() + ' L';
        },

        tickDrivers() {
            const delta = Math.random() > 0.5 ? 1 : -1;
            this.stats.drivers.value = Math.min(148, Math.max(88, this.stats.drivers.value + delta));
        },
    }));
});
