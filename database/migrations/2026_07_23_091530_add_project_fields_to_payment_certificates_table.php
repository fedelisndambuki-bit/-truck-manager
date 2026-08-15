<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_certificates', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('contract_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('payment_certificates', 'progress_claim_id')) {
                $table->foreignId('progress_claim_id')->nullable()->after('project_id')->constrained('progress_claims')->nullOnDelete();
            }
            if (! Schema::hasColumn('payment_certificates', 'scope')) {
                $table->string('scope')->default('subcontractor')->after('certificate_type'); // subcontractor, client
            }
        });

        Schema::table('payment_certificates', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_certificates', function (Blueprint $table) {
            if (Schema::hasColumn('payment_certificates', 'project_id')) {
                $table->dropConstrainedForeignId('project_id');
            }
            if (Schema::hasColumn('payment_certificates', 'progress_claim_id')) {
                $table->dropConstrainedForeignId('progress_claim_id');
            }
            if (Schema::hasColumn('payment_certificates', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};
