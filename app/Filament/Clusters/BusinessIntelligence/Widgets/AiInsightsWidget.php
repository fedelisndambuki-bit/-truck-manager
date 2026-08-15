<?php

namespace App\Filament\Clusters\BusinessIntelligence\Widgets;

use App\Models\AiInsight;
use App\Services\Construction\AiInsightsService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AiInsightsWidget extends Widget
{
    protected static string $view = 'filament.clusters.business-intelligence.widgets.ai-insights';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getInsights()
    {
        $companyId = Auth::user()?->company_id;

        return AiInsight::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->latest('generated_at')
            ->limit(8)
            ->get();
    }

    public function generateInsights(): void
    {
        $companyId = Auth::user()?->company_id;

        $count = count(app(AiInsightsService::class)->generate($companyId));

        Notification::make()
            ->title($count > 0 ? "{$count} new insight(s) generated" : 'No new insights detected — all metrics are within normal range')
            ->success()
            ->send();
    }
}
