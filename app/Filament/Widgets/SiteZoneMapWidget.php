<?php

namespace App\Filament\Widgets;

use App\Models\Site;
use Filament\Widgets\Widget;

class SiteZoneMapWidget extends Widget
{
    public ?Site $record = null;

    protected static string $view = 'filament.widgets.site-zone-map';

    protected int|string|array $columnSpan = 'full';

    public function mount(?Site $record = null): void
    {
        $this->record = $record;
    }

    protected static function zoneColor(string $type): string
    {
        return match ($type) {
            'Building' => '#2563eb',
            'Restricted Area' => '#ef4444',
            'Storage Yard' => '#f59e0b',
            'Equipment Parking' => '#8b5cf6',
            'Material Storage' => '#0ea5e9',
            'Emergency Assembly' => '#dc2626',
            'Office' => '#10b981',
            'Entry Gate' => '#16a34a',
            'Exit Gate' => '#ca8a04',
            default => '#6b7280',
        };
    }

    public function getMapZones()
    {
        if (! $this->record) {
            return collect();
        }

        $zones = $this->record->zones()->whereNotNull('gps_lat')->whereNotNull('gps_lng')->get()
            ->map(fn ($zone) => [
                'name' => $zone->name,
                'type' => $zone->type,
                'lat' => (float) $zone->gps_lat,
                'lng' => (float) $zone->gps_lng,
                'color' => static::zoneColor($zone->type),
            ]);

        // Always include the site's own pin as the anchor marker.
        if ($this->record->gps_lat && $this->record->gps_lng) {
            $zones->prepend([
                'name' => $this->record->name . ' (Site)',
                'type' => 'Site',
                'lat' => (float) $this->record->gps_lat,
                'lng' => (float) $this->record->gps_lng,
                'color' => '#111827',
            ]);
        }

        return $zones->values();
    }
}
