<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\Subcontractor;
use App\Traits\HasIndustryAccess;
use Illuminate\Support\Facades\Auth;

class SubcontractorCompliance extends \Filament\Pages\Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Compliance';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Subcontractor Compliance';

    protected static string $view = 'filament.pages.subcontractor-compliance';

    public function getStats(): array
    {
        $companyId = Auth::user()?->company_id;

        $subcontractors = Subcontractor::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $insuranceExpired = (clone $subcontractors)->whereNotNull('insurance_expiry_date')->where('insurance_expiry_date', '<', now())->count();
        $insuranceExpiring = (clone $subcontractors)->whereBetween('insurance_expiry_date', [now(), now()->addDays(30)])->count();
        $licenseExpired = (clone $subcontractors)->whereNotNull('business_license_expiry_date')->where('business_license_expiry_date', '<', now())->count();
        $licenseExpiring = (clone $subcontractors)->whereBetween('business_license_expiry_date', [now(), now()->addDays(30)])->count();
        $ncaExpired = (clone $subcontractors)->whereNotNull('nca_expiry_date')->where('nca_expiry_date', '<', now())->count();
        $ncaExpiring = (clone $subcontractors)->whereBetween('nca_expiry_date', [now(), now()->addDays(30)])->count();
        $taxNonCompliant = (clone $subcontractors)->where('tax_compliance_status', 'Non-Compliant')->count();

        $contracts = Contract::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $expiringContracts = (clone $contracts)
            ->whereIn('status', ['Active', 'Suspended'])
            ->whereBetween('completion_date', [now(), now()->addDays(30)])
            ->count();

        $total = (clone $subcontractors)->count();
        $nonCompliant = (clone $subcontractors)->get()->filter(fn ($s) => $s->compliance_status !== 'Compliant')->count();

        return [
            'total_subcontractors' => $total,
            'compliant' => $total - $nonCompliant,
            'non_compliant' => $nonCompliant,
            'insurance_expired' => $insuranceExpired,
            'insurance_expiring' => $insuranceExpiring,
            'license_expired' => $licenseExpired,
            'license_expiring' => $licenseExpiring,
            'nca_expired' => $ncaExpired,
            'nca_expiring' => $ncaExpiring,
            'tax_non_compliant' => $taxNonCompliant,
            'expiring_contracts' => $expiringContracts,
        ];
    }

    public function getNonCompliantSubcontractors()
    {
        $companyId = Auth::user()?->company_id;

        return Subcontractor::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn ($s) => $s->compliance_status !== 'Compliant')
            ->values();
    }

    public function getExpiringContracts()
    {
        $companyId = Auth::user()?->company_id;

        return Contract::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Active', 'Suspended'])
            ->whereBetween('completion_date', [now(), now()->addDays(30)])
            ->with('subcontractor')
            ->orderBy('completion_date')
            ->get();
    }
}
