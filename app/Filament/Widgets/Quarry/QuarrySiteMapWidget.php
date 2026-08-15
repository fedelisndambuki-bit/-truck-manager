<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\ExtractionSite;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuarrySiteMapWidget extends Widget
{
    protected static string $view = 'filament.widgets.quarry.site-map';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function getMapSites()
    {
        $companyId = Auth::user()?->company_id;

        return ExtractionSite::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('gps_lat')
            ->whereNotNull('gps_lng')
            ->withCount(['crushingPlants', 'stockpiles', 'weighbridgeTickets'])
            ->get()
            ->map(fn (ExtractionSite $site) => [
                'name' => $site->name,
                'lat' => (float) $site->gps_lat,
                'lng' => (float) $site->gps_lng,
                'status' => $site->status,
                'material' => $site->material_type,
                'plants' => $site->crushing_plants_count,
                'stockpiles' => $site->stockpiles_count,
                'tickets' => $site->weighbridge_tickets_count,
            ])
            ->values();
    }
}
