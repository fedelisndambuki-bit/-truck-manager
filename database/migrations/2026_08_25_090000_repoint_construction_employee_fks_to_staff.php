<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original Construction tables (2026_07_15_1200xx) were built with their
 * "who did this" columns foreign-keyed to the legacy `employees` (fleet)
 * table. The application's Eloquent relations were later repointed to the
 * newer `Staff` HR model (matching how every subsequent Construction table,
 * e.g. subcontractor_assignments, does it) but the database constraints were
 * never migrated to match — so these columns reject Staff IDs while every
 * relation in the codebase expects one.
 */
return new class extends Migration
{
    // Column => original onDelete behavior, preserved when repointing.
    protected array $cascadeColumns = [
        'labourers' => 'employee_id',
        'attendances' => 'employee_id',
    ];

    protected array $nullableColumns = [
        'projects' => 'project_manager_id',
        'sites' => 'site_manager_id',
        'site_inspections' => 'inspector_id',
        'daily_site_diaries' => 'logged_by_id',
        'safety_incidents' => 'reported_by_id',
    ];

    public function up(): void
    {
        foreach ($this->cascadeColumns as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->foreign($column)->references('id')->on('staff')->cascadeOnDelete());
        }

        foreach ($this->nullableColumns as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->foreign($column)->references('id')->on('staff')->nullOnDelete());
        }
    }

    public function down(): void
    {
        foreach ($this->cascadeColumns as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->foreign($column)->references('id')->on('employees')->cascadeOnDelete());
        }

        foreach ($this->nullableColumns as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->foreign($column)->references('id')->on('employees')->nullOnDelete());
        }
    }
};
