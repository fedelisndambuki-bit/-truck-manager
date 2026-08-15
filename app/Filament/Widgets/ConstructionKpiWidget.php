<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Labourer;
use App\Models\MaterialDelivery;
use App\Models\Payment;
use App\Models\Project;
use App\Models\SafetyIncident;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ConstructionKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $currency = session('currency', 'KES');

        $projects = Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $activeProjects = (clone $projects)->where('status', 'Active')->count();
        $completedProjects = (clone $projects)->where('status', 'Completed')->count();
        $delayedProjects = (clone $projects)->where('status', 'Delayed')->count();

        $activeSites = \App\Models\Site::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->count();

        $totalEquipment = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();
        $inUseEquipment = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'In Use')->count();
        $equipmentUtilization = $totalEquipment > 0 ? round(($inUseEquipment / $totalEquipment) * 100) : 0;

        $workersToday = Labourer::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();

        $openIncidents = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Open', 'Investigating'])
            ->count();

        $deliveriesToday = MaterialDelivery::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('delivered_at', today())
            ->count();

        $totalBudget = (clone $projects)->sum('budget');
        $totalSpent = Expense::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('project_id')
            ->sum('amount');
        $budgetUtilization = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100) : 0;

        $revenue = Payment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('project_id')
            ->where('status', 'Paid')
            ->sum('amount');

        $outstanding = Invoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('project_id')
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('total_amount');

        $profitMargin = $revenue > 0 ? round((((float) $revenue - (float) $totalSpent) / (float) $revenue) * 100) : 0;

        return [
            Stat::make('Active Projects', (string) $activeProjects)->descriptionIcon('heroicon-m-building-office-2')->color('success'),
            Stat::make('Completed Projects', (string) $completedProjects)->descriptionIcon('heroicon-m-check-circle')->color('info'),
            Stat::make('Delayed Projects', (string) $delayedProjects)->descriptionIcon('heroicon-m-clock')->color($delayedProjects > 0 ? 'danger' : 'success'),
            Stat::make('Active Sites', (string) $activeSites)->descriptionIcon('heroicon-m-map-pin')->color('info'),
            Stat::make('Equipment Utilization', $equipmentUtilization . '%')->descriptionIcon('heroicon-m-wrench-screwdriver')->color('warning'),
            Stat::make('Workers On Site Today', (string) $workersToday)->descriptionIcon('heroicon-m-user-group')->color('success'),
            Stat::make('Open Safety Incidents', (string) $openIncidents)->descriptionIcon('heroicon-m-exclamation-triangle')->color($openIncidents > 0 ? 'danger' : 'success'),
            Stat::make("Today's Material Deliveries", (string) $deliveriesToday)->descriptionIcon('heroicon-m-truck')->color('info'),
            Stat::make('Budget Utilization', $budgetUtilization . '%')->descriptionIcon('heroicon-m-chart-pie')->color($budgetUtilization > 90 ? 'danger' : 'success'),
            Stat::make('Revenue', $currency . ' ' . number_format((float) $revenue, 0))->descriptionIcon('heroicon-m-banknotes')->color('success'),
            Stat::make('Outstanding Payments', $currency . ' ' . number_format((float) $outstanding, 0))->descriptionIcon('heroicon-m-exclamation-circle')->color('warning'),
            Stat::make('Profit Margin', $profitMargin . '%')->descriptionIcon('heroicon-m-arrow-trending-up')->color($profitMargin >= 0 ? 'success' : 'danger'),
        ];
    }
}
