<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_allocations', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('equipment_id')->constrained()->nullOnDelete();
            $table->string('construction_phase')->nullable()->after('site_id');
            $table->string('department')->nullable()->after('construction_phase');
            $table->string('priority')->nullable()->after('department'); // Low, Medium, High, Critical
            $table->string('allocation_type')->nullable()->after('priority'); // Temporary, Long Term, Emergency

            $table->foreignId('operator_employee_id')->nullable()->after('allocated_to')->constrained('employees')->nullOnDelete();
            $table->foreignId('backup_operator_employee_id')->nullable()->after('operator_employee_id')->constrained('employees')->nullOnDelete();
            $table->foreignId('supervisor_employee_id')->nullable()->after('backup_operator_employee_id')->constrained('employees')->nullOnDelete();
            $table->string('shift')->nullable()->after('supervisor_employee_id');
            $table->date('actual_return_date')->nullable()->after('shift');

            $table->string('purpose')->nullable()->after('actual_return_date');
            $table->decimal('expected_daily_hours', 6, 2)->nullable()->after('purpose');
            $table->decimal('expected_fuel_usage', 8, 2)->nullable()->after('expected_daily_hours');
            $table->string('expected_production')->nullable()->after('expected_fuel_usage');
            $table->string('working_zone')->nullable()->after('expected_production');
            $table->string('risk_level')->nullable()->after('working_zone'); // Low, Medium, High, Critical
            $table->text('special_instructions')->nullable()->after('risk_level');

            $table->string('approval_status')->default('Pending')->after('special_instructions'); // Pending, Approved, Rejected
            $table->foreignId('approved_by_id')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable()->after('approved_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('operator_employee_id');
            $table->dropConstrainedForeignId('backup_operator_employee_id');
            $table->dropConstrainedForeignId('supervisor_employee_id');
            $table->dropConstrainedForeignId('approved_by_id');
            $table->dropColumn([
                'construction_phase', 'department', 'priority', 'allocation_type', 'shift',
                'actual_return_date', 'purpose', 'expected_daily_hours', 'expected_fuel_usage',
                'expected_production', 'working_zone', 'risk_level', 'special_instructions',
                'approval_status', 'approved_at',
            ]);
        });
    }
};
