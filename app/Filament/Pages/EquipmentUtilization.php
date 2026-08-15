<?php

namespace App\Filament\Pages;

use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Traits\HasIndustryAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class EquipmentUtilization extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Equipment Utilization';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Equipment Utilization';

    protected static string $view = 'filament.pages.equipment-utilization';

    public function getEquipmentReport()
    {
        $companyId = Auth::user()?->company_id;

        return Equipment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->withSum(['allocations as hours_today' => function ($q) {
                $q->whereDate('created_at', today());
            }], 'operating_hours_today')
            ->withSum(['allocations as fuel_today' => function ($q) {
                $q->whereDate('created_at', today());
            }], 'fuel_consumed_today')
            ->withCount('allocations')
            ->orderByDesc('hours_today')
            ->get();
    }

    public function getStats(): array
    {
        $companyId = Auth::user()?->company_id;

        $total = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();
        $inUse = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'In Use')->count();
        $idle = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Available')->count();
        $maintenance = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Under Maintenance')->count();

        $totalHoursToday = EquipmentAllocation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today())
            ->sum('operating_hours_today');

        $totalFuelToday = EquipmentAllocation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today())
            ->sum('fuel_consumed_today');

        return [
            'total' => $total,
            'in_use' => $inUse,
            'idle' => $idle,
            'maintenance' => $maintenance,
            'utilization_percent' => $total > 0 ? round(($inUse / $total) * 100) : 0,
            'hours_today' => $totalHoursToday,
            'fuel_today' => $totalFuelToday,
        ];
    }
}
