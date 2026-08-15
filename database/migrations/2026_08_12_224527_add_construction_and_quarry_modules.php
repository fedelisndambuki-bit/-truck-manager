<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The `modules` table was seeded (in 2026_03_04_110948_create_modules_table)
     * with only the 16 Fleet/Trucking modules. Construction and Quarry gained
     * their own full permission-gated module system later (see the dot-notation
     * permissions in database/seeders/RoleSeeder.php — e.g. `blast.view`,
     * `eam.view`, `documents.view`), but those were never mirrored into this
     * table, so the Plans pricing UI (which reads from `modules`) had no way
     * to represent them. This adds one row per real module domain, using the
     * exact same slug as its Spatie permission for consistency.
     */
    public function up(): void
    {
        $modules = [
            // Quarry
            ['name' => 'Blast Management', 'slug' => 'blast.view', 'category' => 'Quarry', 'icon' => 'heroicon-o-bolt'],
            ['name' => 'Crushing Plant Management', 'slug' => 'plant.view', 'category' => 'Quarry', 'icon' => 'heroicon-o-cog-8-tooth'],
            ['name' => 'Dispatch Management', 'slug' => 'dispatch.view', 'category' => 'Quarry', 'icon' => 'heroicon-o-truck'],
            ['name' => 'Equipment & Asset Management', 'slug' => 'eam.view', 'category' => 'Quarry', 'icon' => 'heroicon-o-wrench-screwdriver'],
            ['name' => 'Laboratory & Quality Control', 'slug' => 'lab.view', 'category' => 'Quarry', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Production Management', 'slug' => 'production.view', 'category' => 'Quarry', 'icon' => 'heroicon-o-chart-bar'],
            ['name' => 'Smart Weighbridge', 'slug' => 'weighbridge.view', 'category' => 'Quarry', 'icon' => 'heroicon-o-scale'],
            ['name' => 'Stockpile Management', 'slug' => 'stockpile.view', 'category' => 'Quarry', 'icon' => 'heroicon-o-cube'],
            // Construction
            ['name' => 'Document Management', 'slug' => 'documents.view', 'category' => 'Construction', 'icon' => 'heroicon-o-document-duplicate'],
            ['name' => 'Project Finance', 'slug' => 'project_finance.view', 'category' => 'Construction', 'icon' => 'heroicon-o-banknotes'],
            ['name' => 'Subcontractor Management', 'slug' => 'subcontractors.view', 'category' => 'Construction', 'icon' => 'heroicon-o-briefcase'],
            ['name' => 'Materials Management', 'slug' => 'materials.view', 'category' => 'Construction', 'icon' => 'heroicon-o-archive-box'],
            // Shared between Construction and Quarry
            ['name' => 'Safety & Compliance', 'slug' => 'safety.view', 'category' => 'Safety', 'icon' => 'heroicon-o-shield-check'],
            ['name' => 'HR & Workforce Management', 'slug' => 'hr.view', 'category' => 'Workforce', 'icon' => 'heroicon-o-user-group'],
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
            'blast.view', 'plant.view', 'dispatch.view', 'eam.view', 'lab.view',
            'production.view', 'weighbridge.view', 'stockpile.view',
            'documents.view', 'project_finance.view', 'subcontractors.view', 'materials.view',
            'safety.view', 'hr.view',
        ])->delete();
    }
};
