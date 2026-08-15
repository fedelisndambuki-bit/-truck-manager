<?php

namespace App\Filament\Clusters\BusinessIntelligence\Widgets;

use App\Models\DailySiteDiary;
use App\Models\EquipmentAllocation;
use App\Models\Labourer;
use App\Models\Site;
use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class BiSiteMapWidget extends Widget
{
    protected static string $view = 'filament.clusters.business-intelligence.widgets.bi-site-map';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getMapSites()
    {
        $companyId = Auth::user()?->company_id;
        $finance = app(ProjectFinanceService::class);
        $currency = session('currency', 'KES');

        return Site::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('gps_lat')
            ->whereNotNull('gps_lng')
            ->with(['project', 'siteManager'])
            ->get()
            ->map(function (Site $site) use ($finance, $currency) {
                $latestDiary = DailySiteDiary::query()->where('site_id', $site->id)->latest('date')->first();

                return [
                    'name' => $site->project?->name ?? $site->name,
                    'lat' => (float) $site->gps_lat,
                    'lng' => (float) $site->gps_lng,
                    'progress' => $site->project?->progress_percent ?? 0,
                    'manager' => $site->siteManager?->full_name ?? 'Unassigned',
                    'status' => $site->status,
                    'budget' => $site->project ? $currency . ' ' . number_format($finance->budgetAtCompletion($site->project), 0) : '—',
                    'workforce' => Labourer::query()->where('site_id', $site->id)->count(),
                    'equipment' => EquipmentAllocation::query()->where('site_id', $site->id)->whereNull('actual_return_date')->count(),
                    'weather' => $latestDiary?->weather ?? 'No data',
                ];
            })
            ->values();
    }
}
