<?php

namespace App\Filament\Widgets;

use App\Models\MaterialDelivery;
use App\Models\Project;
use App\Models\SiteInspection;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UpcomingTasksWidget extends Widget
{
    protected static string $view = 'filament.widgets.upcoming-tasks';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 11;

    public function getTasks(): Collection
    {
        $companyId = Auth::user()?->company_id;

        $deadlines = Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('end_date')
            ->where('end_date', '>=', now())
            ->get()
            ->map(fn (Project $p) => [
                'type' => 'Project Deadline',
                'description' => $p->name,
                'date' => $p->end_date,
            ]);

        $inspections = SiteInspection::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('date', '>=', now())
            ->with('site')
            ->get()
            ->map(fn (SiteInspection $i) => [
                'type' => 'Inspection',
                'description' => ($i->site?->name ?? 'Site') . ' — ' . $i->type,
                'date' => $i->date,
            ]);

        $deliveries = MaterialDelivery::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Pending')
            ->whereNotNull('delivered_at')
            ->with(['material', 'site'])
            ->get()
            ->map(fn (MaterialDelivery $d) => [
                'type' => 'Material Delivery',
                'description' => ($d->material?->name ?? 'Material') . ' to ' . ($d->site?->name ?? 'site'),
                'date' => $d->delivered_at,
            ]);

        return $deadlines->concat($inspections)->concat($deliveries)
            ->sortBy('date')
            ->take(10)
            ->values();
    }
}
