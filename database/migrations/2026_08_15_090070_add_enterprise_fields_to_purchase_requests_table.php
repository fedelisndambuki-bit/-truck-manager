<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('business_unit_id')->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('extraction_site_id')->nullable()->after('project_id')->constrained('extraction_sites')->nullOnDelete();
            $table->foreignId('workshop_id')->nullable()->after('extraction_site_id')->constrained('workshops')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('workshop_id')->constrained()->nullOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->after('warehouse_id')->constrained('cost_centres')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('cost_centre_id')->constrained()->nullOnDelete();
            $table->string('currency')->default('KES')->after('supplier_id');
            $table->string('budget_code')->nullable()->after('currency');
            $table->decimal('expected_cost', 14, 2)->nullable()->after('estimated_cost');
            $table->text('reason')->nullable()->after('expected_cost');

            // Legacy single material/quantity header stays for generateFromLowStock()'s
            // auto-PR path — new multi-line requisitions use purchase_request_items instead.
            $table->unsignedBigInteger('material_id')->nullable()->change();
            $table->decimal('quantity', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_unit_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('extraction_site_id');
            $table->dropConstrainedForeignId('workshop_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('cost_centre_id');
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['currency', 'budget_code', 'expected_cost', 'reason']);
        });
    }
};
