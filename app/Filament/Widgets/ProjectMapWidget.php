<?php

namespace App\Filament\Widgets;

use App\Models\Site;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ProjectMapWidget extends Widget
{
    protected static string $view = 'filament.widgets.project-map';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    public function getMapSites()
    {
        $companyId = Auth::user()?->company_id;

        return Site::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('gps_lat')
            ->whereNotNull('gps_lng')
            ->with(['project', 'siteManager'])
            ->get()
            ->map(fn (Site $site) => [
                'name' => $site->project?->name ?? $site->name,
                'lat' => (float) $site->gps_lat,
                'lng' => (float) $site->gps_lng,
                'progress' => $site->project?->progress_percent ?? 0,
                'manager' => $site->siteManager?->full_name ?? 'Unassigned',
                'status' => $site->status,
            ])
            ->values();
    }
}
