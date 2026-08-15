<?php

namespace App\Providers;

use App\Models\AiLog;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\Site;
use App\Models\Staff;
use App\Models\Subcontractor;
use App\Policies\AIPolicy;
use App\Policies\EquipmentPolicy;
use App\Policies\MaterialPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SitePolicy;
use App\Policies\StaffPolicy;
use App\Policies\SubcontractorPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AiLog::class => AIPolicy::class,
        Project::class => ProjectPolicy::class,
        Site::class => SitePolicy::class,
        Equipment::class => EquipmentPolicy::class,
        Staff::class => StaffPolicy::class,
        Material::class => MaterialPolicy::class,
        Subcontractor::class => SubcontractorPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('accessAI', [AIPolicy::class, 'accessAI']);
        Gate::define('viewLogs', [AIPolicy::class, 'viewLogs']);
        Gate::define('deleteLogs', [AIPolicy::class, 'deleteLogs']);
    }
}
