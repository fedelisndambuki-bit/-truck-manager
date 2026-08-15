<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ProjectProgressWidget extends Widget
{
    protected static string $view = 'filament.widgets.project-progress';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getProjects()
    {
        $companyId = Auth::user()?->company_id;

        return Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Planning', 'Active', 'Delayed', 'On Hold'])
            ->with(['client', 'projectManager'])
            ->latest()
            ->limit(6)
            ->get();
    }
}
