<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DocumentManagementService::createVersion() writes both `current_version`
     * and `revision_number` onto whichever entity it versions — these three
     * tables have `current_version` but were missing `revision_number`.
     */
    public function up(): void
    {
        foreach (['drawings', 'method_statements', 'technical_submittals'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'revision_number')) {
                    $table->unsignedInteger('revision_number')->default(0)->after('current_version');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['drawings', 'method_statements', 'technical_submittals'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'revision_number')) {
                    $table->dropColumn('revision_number');
                }
            });
        }
    }
};
