<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labourers', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('employee_id')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->after('department_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->after('supervisor_id')->constrained()->nullOnDelete();
            $table->string('construction_phase')->nullable()->after('role_on_site');
            $table->date('expected_end_date')->nullable()->after('construction_phase');
            $table->string('approval_status')->default('Pending')->after('daily_rate'); // Pending, Approved, Rejected
            $table->foreignId('approved_by_id')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable()->after('approved_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('labourers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropConstrainedForeignId('shift_id');
            $table->dropConstrainedForeignId('approved_by_id');
            $table->dropColumn(['construction_phase', 'expected_end_date', 'approval_status', 'approved_at']);
        });
    }
};
