<?php

namespace App\Filament\Widgets;

use App\Models\DailySiteDiary;
use App\Models\EquipmentAllocation;
use App\Models\MaterialDelivery;
use App\Models\QualityInspection;
use App\Models\SafetyIncident;
use App\Models\SiteInspection;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SiteActivityWidget extends Widget
{
    protected static string $view = 'filament.widgets.site-activity';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getActivity()
    {
        $companyId = Auth::user()?->company_id;
        $scope = fn ($q) => $q->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $deliveries = MaterialDelivery::query()->tap($scope)->with(['material', 'site'])->latest('created_at')->limit(5)->get()
            ->map(fn ($d) => [
                'type' => 'delivery',
                'icon' => 'heroicon-o-truck',
                'color' => 'info',
                'text' => "Material delivered: {$d->material?->name} to {$d->site?->name}",
                'time' => $d->created_at,
            ]);

        $allocations = EquipmentAllocation::query()->tap($scope)->with(['equipment', 'site'])->latest('created_at')->limit(5)->get()
            ->map(fn ($a) => [
                'type' => 'equipment',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'color' => 'warning',
                'text' => "Equipment assigned: {$a->equipment?->name} to {$a->site?->name}",
                'time' => $a->created_at,
            ]);

        $inspections = SiteInspection::query()->tap($scope)->with('site')->latest('created_at')->limit(5)->get()
            ->map(fn ($i) => [
                'type' => 'inspection',
                'icon' => 'heroicon-o-clipboard-document-check',
                'color' => 'success',
                'text' => "Site inspection completed at {$i->site?->name} ({$i->result})",
                'time' => $i->created_at,
            ]);

        $incidents = SafetyIncident::query()->tap($scope)->with('site')->latest('created_at')->limit(5)->get()
            ->map(fn ($i) => [
                'type' => 'incident',
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
                'text' => "Safety incident reported at {$i->site?->name} ({$i->severity})",
                'time' => $i->created_at,
            ]);

        $quality = QualityInspection::query()->tap($scope)->with('site')->latest('created_at')->limit(5)->get()
            ->map(fn ($q) => [
                'type' => 'quality',
                'icon' => 'heroicon-o-check-badge',
                'color' => 'success',
                'text' => "Quality check at {$q->site?->name}: {$q->checklist_item} ({$q->result})",
                'time' => $q->created_at,
            ]);

        $diaries = DailySiteDiary::query()->tap($scope)->with('site')->latest('created_at')->limit(5)->get()
            ->map(fn ($d) => [
                'type' => 'diary',
                'icon' => 'heroicon-o-book-open',
                'color' => 'gray',
                'text' => "Daily report submitted for {$d->site?->name} ({$d->workers_on_site} workers)",
                'time' => $d->created_at,
            ]);

        return $deliveries->concat($allocations)->concat($inspections)->concat($incidents)->concat($quality)->concat($diaries)
            ->sortByDesc('time')
            ->take(10)
            ->values();
    }
}
