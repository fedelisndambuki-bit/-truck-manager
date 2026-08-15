<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('access_requests', function (Blueprint $table) {
            // Four-eyes control: an Enterprise-tier approval requires sign-off
            // from a SECOND, distinct Super Admin before it actually finalizes.
            $table->foreignId('first_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_approved_at')->nullable();
            $table->foreignId('first_approved_plan_id')->nullable()->constrained('plans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('first_approved_by');
            $table->dropConstrainedForeignId('first_approved_plan_id');
            $table->dropColumn('first_approved_at');
        });
    }
};
