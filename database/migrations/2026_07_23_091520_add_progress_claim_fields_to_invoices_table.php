<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'progress_claim_id')) {
                $table->foreignId('progress_claim_id')->nullable()->after('project_id')->constrained('progress_claims')->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'type')) {
                $table->string('type')->default('invoice')->after('status'); // invoice, credit_note
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'progress_claim_id')) {
                $table->dropConstrainedForeignId('progress_claim_id');
            }
            if (Schema::hasColumn('invoices', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
