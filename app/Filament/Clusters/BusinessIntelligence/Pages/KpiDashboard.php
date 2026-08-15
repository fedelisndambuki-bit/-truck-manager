<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\BiDashboardWidget;
use App\Services\Construction\BusinessIntelligenceService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class KpiDashboard extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'bi.view';
    }

    protected static ?string $cluster = BusinessIntelligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'KPI Dashboard';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'KPI Dashboard';

    protected static string $view = 'filament.clusters.business-intelligence.pages.kpi-dashboard';

    /** @var array<string, array{label: string, icon: string, format: string}> */
    public static function catalog(): array
    {
        return [
            'total_projects' => ['label' => 'Total Projects', 'icon' => 'heroicon-o-building-office-2', 'format' => 'int'],
            'active_projects' => ['label' => 'Active Projects', 'icon' => 'heroicon-o-play-circle', 'format' => 'int'],
            'projects_at_risk' => ['label' => 'Projects at Risk', 'icon' => 'heroicon-o-exclamation-triangle', 'format' => 'int'],
            'total_contract_value' => ['label' => 'Total Contract Value', 'icon' => 'heroicon-o-document-text', 'format' => 'currency'],
            'total_revenue' => ['label' => 'Total Revenue', 'icon' => 'heroicon-o-arrow-trending-up', 'format' => 'currency'],
            'total_cost' => ['label' => 'Total Cost', 'icon' => 'heroicon-o-arrow-trending-down', 'format' => 'currency'],
            'profit' => ['label' => 'Profit', 'icon' => 'heroicon-o-banknotes', 'format' => 'currency'],
            'profit_margin' => ['label' => 'Profit Margin', 'icon' => 'heroicon-o-chart-pie', 'format' => 'percent'],
            'budget_utilization' => ['label' => 'Budget Utilization', 'icon' => 'heroicon-o-scale', 'format' => 'percent'],
            'cash_flow_position' => ['label' => 'Cash Flow Position', 'icon' => 'heroicon-o-currency-dollar', 'format' => 'currency'],
            'workforce_count' => ['label' => 'Workforce Count', 'icon' => 'heroicon-o-user-group', 'format' => 'int'],
            'equipment_utilization' => ['label' => 'Equipment Utilization', 'icon' => 'heroicon-o-wrench-screwdriver', 'format' => 'percent'],
            'material_cost' => ['label' => 'Material Cost', 'icon' => 'heroicon-o-cube', 'format' => 'currency'],
            'safety_score' => ['label' => 'Safety Score', 'icon' => 'heroicon-o-shield-check', 'format' => 'score'],
            'quality_score' => ['label' => 'Quality Score', 'icon' => 'heroicon-o-check-badge', 'format' => 'percent'],
            'client_satisfaction' => ['label' => 'Client Satisfaction', 'icon' => 'heroicon-o-face-smile', 'format' => 'percent_nullable'],
            'project_completion_rate' => ['label' => 'Project Completion Rate', 'icon' => 'heroicon-o-flag', 'format' => 'percent'],
        ];
    }

    protected function widgetsForUser(): \Illuminate\Support\Collection
    {
        $userId = Auth::id();
        $catalog = static::catalog();

        $existing = BiDashboardWidget::query()->where('user_id', $userId)->get()->keyBy('widget_key');

        $order = 0;
        foreach (array_keys($catalog) as $key) {
            if (! $existing->has($key)) {
                $existing->put($key, BiDashboardWidget::create([
                    'user_id' => $userId,
                    'widget_key' => $key,
                    'sort_order' => $order,
                    'is_visible' => true,
                ]));
            }
            $order++;
        }

        return $existing->sortBy('sort_order')->values();
    }

    public function getWidgets(): array
    {
        $bi = app(BusinessIntelligenceService::class);
        $companyId = Auth::user()?->company_id;
        $currency = session('currency', 'KES');
        $catalog = static::catalog();

        return $this->widgetsForUser()->map(function (BiDashboardWidget $w) use ($bi, $companyId, $currency, $catalog) {
            $meta = $catalog[$w->widget_key] ?? null;

            if (! $meta) {
                return null;
            }

            $method = str($w->widget_key)->camel()->toString();
            $raw = method_exists($bi, $method) ? $bi->{$method}($companyId) : null;

            $value = match ($meta['format']) {
                'currency' => $currency . ' ' . number_format((float) $raw, 0),
                'percent' => number_format((float) $raw, 1) . '%',
                'percent_nullable' => $raw === null ? 'No ratings yet' : number_format((float) $raw, 1) . '%',
                'score' => number_format((float) $raw, 1) . ' / 100',
                default => (string) $raw,
            };

            return [
                'widget' => $w,
                'key' => $w->widget_key,
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'value' => $value,
                'is_visible' => $w->is_visible,
            ];
        })->filter()->values()->all();
    }

    public function toggleVisibility(string $key): void
    {
        BiDashboardWidget::query()->where('user_id', Auth::id())->where('widget_key', $key)
            ->update(['is_visible' => ! (bool) BiDashboardWidget::query()->where('user_id', Auth::id())->where('widget_key', $key)->value('is_visible')]);
    }

    public function moveUp(string $key): void
    {
        $this->swap($key, -1);
    }

    public function moveDown(string $key): void
    {
        $this->swap($key, 1);
    }

    protected function swap(string $key, int $direction): void
    {
        $widgets = $this->widgetsForUser();
        $index = $widgets->search(fn (BiDashboardWidget $w) => $w->widget_key === $key);

        if ($index === false) {
            return;
        }

        $targetIndex = $index + $direction;

        if ($targetIndex < 0 || $targetIndex >= $widgets->count()) {
            return;
        }

        $current = $widgets->get($index);
        $target = $widgets->get($targetIndex);

        $currentOrder = $current->sort_order;
        $current->update(['sort_order' => $target->sort_order]);
        $target->update(['sort_order' => $currentOrder]);
    }
}
