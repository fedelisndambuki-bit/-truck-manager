<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\PredictiveMaintenanceService;

class PredictiveMaintenanceRisks extends Widget
{
    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 2;

    protected static string $view = 'filament.widgets.predictive-maintenance-risks';

    public array $risks = [];

    public function mount(): void
    {
        $this->loadRisks();
    }

    public function refreshRisks(): void
    {
        $this->loadRisks(force: true);
    }

    private function loadRisks(bool $force = false): void
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? ($user->company?->id ?? null);
        if (! $companyId) {
            $this->risks = [];
            return;
        }
        $svc = app(PredictiveMaintenanceService::class);
        $this->risks = $svc->topRisks($companyId, 5);
    }
}

