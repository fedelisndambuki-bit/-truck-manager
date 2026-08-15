<?php

namespace App\Filament\Pages;

use App\Models\Department;
use App\Models\Staff;
use App\Traits\HasIndustryAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LabourPlanning extends \Filament\Pages\Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Labour Planning';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'Labour Planning';

    protected static string $view = 'filament.pages.labour-planning';

    public function getDepartmentBreakdown()
    {
        $companyId = Auth::user()?->company_id;

        return Department::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->withCount('staff')
            ->withCount('positions')
            ->with(['staff' => fn ($q) => $q->select('id', 'department_id', 'basic_salary', 'allowances')])
            ->get()
            ->map(function (Department $department) {
                $laborCost = $department->staff->sum(fn (Staff $s) => (float) ($s->basic_salary ?? 0) + (float) ($s->allowances ?? 0));

                return [
                    'name' => $department->name,
                    'staff_count' => $department->staff_count,
                    'positions_count' => $department->positions_count,
                    'monthly_labour_cost' => $laborCost,
                ];
            });
    }

    public function getVacancySummary()
    {
        if (! Schema::hasTable('job_vacancies')) {
            return collect();
        }

        $companyId = Auth::user()?->company_id;

        return \App\Models\JobVacancy::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Open')
            ->with('department')
            ->get()
            ->groupBy(fn ($vacancy) => $vacancy->department?->name ?? 'Unassigned')
            ->map(fn ($group) => $group->sum('openings_count'));
    }

    public function getStats(): array
    {
        $companyId = Auth::user()?->company_id;

        $staffQuery = Staff::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $totalStaff = (clone $staffQuery)->count();
        $totalCost = (clone $staffQuery)->where('status', 'Active')
            ->get(['basic_salary', 'allowances'])
            ->sum(fn (Staff $s) => (float) ($s->basic_salary ?? 0) + (float) ($s->allowances ?? 0));

        $openVacancies = Schema::hasTable('job_vacancies')
            ? \App\Models\JobVacancy::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('status', 'Open')
                ->sum('openings_count')
            : 0;

        return [
            'total_staff' => $totalStaff,
            'monthly_labour_cost' => $totalCost,
            'open_vacancies' => $openVacancies,
        ];
    }
}
