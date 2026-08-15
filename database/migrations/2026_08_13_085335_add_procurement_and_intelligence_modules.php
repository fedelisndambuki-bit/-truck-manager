<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Same gap as 2026_08_12_224527_add_construction_and_quarry_modules: these
     * capabilities are already referenced elsewhere in the app (the login
     * page's module grid, the dashboard-preview mockup, and the landing page's
     * Features section all list Procurement/Analytics/Reports as real Tanaki
     * capabilities) but were never added as `modules` catalog rows, so they
     * couldn't be attached to a Plan or shown in the landing page's module
     * catalog section.
     */
    public function up(): void
    {
        $modules = [
            ['name' => 'Procurement', 'slug' => 'procurement.view', 'category' => 'Business', 'icon' => 'heroicon-o-shopping-cart'],
            ['name' => 'Analytics', 'slug' => 'analytics.view', 'category' => 'Intelligence', 'icon' => 'heroicon-o-presentation-chart-line'],
            ['name' => 'Reports', 'slug' => 'reports.view', 'category' => 'Intelligence', 'icon' => 'heroicon-o-chart-bar'],
            ['name' => 'Notifications', 'slug' => 'notifications.view', 'category' => 'Intelligence', 'icon' => 'heroicon-o-bell-alert'],
            ['name' => 'API Integrations', 'slug' => 'api-integrations.view', 'category' => 'Intelligence', 'icon' => 'heroicon-o-code-bracket'],
            ['name' => 'Hardware Integrations', 'slug' => 'hardware-integrations.view', 'category' => 'Intelligence', 'icon' => 'heroicon-o-cpu-chip'],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(
                ['slug' => $module['slug']],
                array_merge($module, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('modules')->whereIn('slug', [
            'procurement.view', 'analytics.view', 'reports.view',
            'notifications.view', 'api-integrations.view', 'hardware-integrations.view',
        ])->delete();
    }
};
