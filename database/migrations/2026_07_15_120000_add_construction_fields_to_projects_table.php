<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'project_manager_id')) {
                $table->foreignId('project_manager_id')->nullable()->after('client_id')->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('projects', 'contract_value')) {
                $table->decimal('contract_value', 14, 2)->nullable()->after('budget');
            }
            if (!Schema::hasColumn('projects', 'progress_percent')) {
                $table->unsignedTinyInteger('progress_percent')->default(0)->after('contract_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_manager_id']);
            $table->dropColumn(['project_manager_id', 'contract_value', 'progress_percent']);
        });
    }
};
