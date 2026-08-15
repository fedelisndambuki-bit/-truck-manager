<?php

namespace App\Filament\Clusters\ProductionManagement\Pages;

use App\Filament\Clusters\ProductionManagement;
use App\Filament\Clusters\ProductionManagement\Widgets\YieldAnalysisChart;
use App\Models\ProductionLog;
use App\Services\Quarry\QuarryAnalyticsService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ProductYield extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'production.view';
    }

    protected static ?string $cluster = ProductionManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Product Yield';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Product Yield Analysis';

    protected static string $view = 'filament.clusters.production-management.pages.product-yield';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />',
            'title' => 'Product Yield',
            'subtitle' => 'Feed vs finished quantity, waste, recovery % and yield % across approved production records.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [YieldAnalysisChart::class];
    }

    public function getSummary(): array
    {
        $companyId = Auth::user()?->company_id;
        $qa = app(QuarryAnalyticsService::class);

        return [
            'yield_percent' => $qa->yieldPercent($companyId),
            'waste_percent' => $qa->wastePercent($companyId),
        ];
    }

    public function getBreakdown(): array
    {
        $companyId = Auth::user()?->company_id;

        return ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Approved')
            ->whereNotNull('feed_quantity')
            ->with('extractionSite')
            ->latest('date')
            ->limit(30)
            ->get()
            ->map(fn (ProductionLog $l) => [
                'production_number' => $l->production_number,
                'site' => $l->extractionSite?->name,
                'date' => $l->date->format('d M Y'),
                'feed' => (float) $l->feed_quantity,
                'finished' => (float) $l->finished_product_quantity,
                'waste' => (float) $l->waste_material,
                'recovery' => (float) $l->recovery_percent,
                'yield' => (float) $l->yield_percent,
            ])
            ->all();
    }
}
