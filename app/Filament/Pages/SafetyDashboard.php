<?php

namespace App\Filament\Pages;

use App\Models\CorrectiveAction;
use App\Models\Hazard;
use App\Models\SafetyIncident;
use App\Traits\HasIndustryAccess;
use Illuminate\Support\Facades\Auth;

class SafetyDashboard extends \Filament\Pages\Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Safety & Incident Management';

    protected static string $view = 'filament.pages.safety-dashboard';

    public function getRecentIncidents()
    {
        $companyId = Auth::user()?->company_id;

        return SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('site')
            ->latest('date')
            ->limit(6)
            ->get();
    }

    public function getTopHazards()
    {
        $companyId = Auth::user()?->company_id;

        return Hazard::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('risk_rating', ['High', 'Critical'])
            ->whereNotIn('status', ['Closed'])
            ->with('site')
            ->latest()
            ->limit(6)
            ->get();
    }

    public function getOverdueCorrectiveActions()
    {
        $companyId = Auth::user()?->company_id;

        return CorrectiveAction::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('due_date', '<', now())
            ->whereNotIn('status', ['Completed', 'Verified', 'Closed'])
            ->with('responsibleStaff')
            ->orderBy('due_date')
            ->limit(6)
            ->get();
    }
}
