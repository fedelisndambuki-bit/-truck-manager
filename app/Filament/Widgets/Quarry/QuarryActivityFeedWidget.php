<?php

namespace App\Filament\Widgets\Quarry;

use App\Services\Quarry\QuarryAnalyticsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuarryActivityFeedWidget extends Widget
{
    protected static string $view = 'filament.widgets.quarry.activity-feed';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 6;

    public function getActivity(): array
    {
        return app(QuarryAnalyticsService::class)->recentActivity(Auth::user()?->company_id);
    }
}
