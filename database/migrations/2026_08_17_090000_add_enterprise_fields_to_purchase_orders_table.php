<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('business_unit_id')->constrained()->nullOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->after('department_id')->constrained('cost_centres')->nullOnDelete();
            $table->foreignId('request_for_quotation_id')->nullable()->after('purchase_request_id')->constrained()->nullOnDelete();
            $table->foreignId('supplier_quotation_id')->nullable()->after('request_for_quotation_id')->constrained()->nullOnDelete();
            $table->foreignId('blanket_purchase_order_id')->nullable()->after('supplier_quotation_id')->constrained()->nullOnDelete();

            // WorkflowEngine::reflectOutcomeOnApprovable() only mirrors an
            // approval outcome onto a column literally named `status` — the
            // existing `approval_status` column stays for the old top-level
            // PurchaseOrderResource, kept in sync by the model's saving hook.
            $table->string('status')->default('Draft')->after('approval_status');
        });

        DB::table('purchase_orders')->update(['status' => DB::raw('approval_status')]);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_unit_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('cost_centre_id');
            $table->dropConstrainedForeignId('request_for_quotation_id');
            $table->dropConstrainedForeignId('supplier_quotation_id');
            $table->dropConstrainedForeignId('blanket_purchase_order_id');
            $table->dropColumn('status');
        });
    }
};
