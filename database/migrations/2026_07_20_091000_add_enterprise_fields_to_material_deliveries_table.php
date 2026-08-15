<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_deliveries', function (Blueprint $table) {
            $table->string('delivery_number')->nullable()->unique()->after('id');
            $table->foreignId('supplier_id')->nullable()->after('material_id')->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->after('purchase_order_ref')->constrained()->nullOnDelete();
            $table->json('photos')->nullable()->after('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('material_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('purchase_order_id');
            $table->dropColumn(['delivery_number', 'photos']);
        });
    }
};
