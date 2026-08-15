<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->foreignId('eam_store_id')->nullable()->after('company_id')->constrained('eam_stores')->nullOnDelete();
            $table->string('bin_location')->nullable()->after('eam_store_id');
            $table->integer('min_stock')->nullable()->after('reorder_level');
            $table->integer('max_stock')->nullable()->after('min_stock');
            $table->integer('reserved_quantity')->default(0)->after('max_stock');
            $table->string('supplier')->nullable()->after('reserved_quantity');
            $table->string('supplier_contact')->nullable()->after('supplier');
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('eam_store_id');
            $table->dropColumn(['bin_location', 'min_stock', 'max_stock', 'reserved_quantity', 'supplier', 'supplier_contact']);
        });
    }
};
