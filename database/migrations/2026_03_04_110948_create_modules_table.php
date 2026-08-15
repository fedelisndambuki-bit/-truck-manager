<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $modules = [
            ['name' => 'Fleet Management', 'slug' => 'fleet-management', 'category' => 'Core', 'icon' => 'heroicon-o-truck'],
            ['name' => 'Driver Management', 'slug' => 'driver-management', 'category' => 'Core', 'icon' => 'heroicon-o-users'],
            ['name' => 'Trip Tracking', 'slug' => 'trip-tracking', 'category' => 'Operations', 'icon' => 'heroicon-o-map-pin'],
            ['name' => 'Fuel Logs', 'slug' => 'fuel-logs', 'category' => 'Operations', 'icon' => 'heroicon-o-fire'],
            ['name' => 'Maintenance', 'slug' => 'maintenance', 'category' => 'Operations', 'icon' => 'heroicon-o-wrench-screwdriver'],
            ['name' => 'Inventory', 'slug' => 'inventory', 'category' => 'Operations', 'icon' => 'heroicon-o-archive-box'],
            ['name' => 'Parts & Tyres', 'slug' => 'parts-tyres', 'category' => 'Operations', 'icon' => 'heroicon-o-cog-6-tooth'],
            ['name' => 'POD Management', 'slug' => 'pod-management', 'category' => 'Operations', 'icon' => 'heroicon-o-document-check'],
            ['name' => 'Invoicing', 'slug' => 'invoicing', 'category' => 'Finance', 'icon' => 'heroicon-o-document-currency-dollar'],
            ['name' => 'Payroll', 'slug' => 'payroll', 'category' => 'Finance', 'icon' => 'heroicon-o-banknotes'],
            ['name' => 'Expense Tracking', 'slug' => 'expense-tracking', 'category' => 'Finance', 'icon' => 'heroicon-o-presentation-chart-line'],
            ['name' => 'Staff Directory', 'slug' => 'hr-staff', 'category' => 'HR', 'icon' => 'heroicon-o-identification'],
            ['name' => 'Leave Management', 'slug' => 'hr-leave', 'category' => 'HR', 'icon' => 'heroicon-o-calendar-days'],
            ['name' => 'AI Insights', 'slug' => 'ai-insights', 'category' => 'Support', 'icon' => 'heroicon-o-sparkles'],
            ['name' => 'Support Tickets', 'slug' => 'support-tickets', 'category' => 'Support', 'icon' => 'heroicon-o-ticket'],
            ['name' => 'System Logs', 'slug' => 'system-logs', 'category' => 'Developer', 'icon' => 'heroicon-o-list-bullet'],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(
                ['slug' => $module['slug']],
                array_merge($module, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
