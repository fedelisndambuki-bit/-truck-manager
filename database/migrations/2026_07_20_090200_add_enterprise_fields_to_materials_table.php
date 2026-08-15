<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('material_code')->nullable()->unique()->after('id');
            $table->foreignId('category_id')->nullable()->after('name')->constrained('material_categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->after('category_id')->constrained('material_categories')->nullOnDelete();
            $table->text('description')->nullable()->after('subcategory_id');
            $table->decimal('standard_cost', 10, 2)->nullable()->after('unit_cost');
            $table->decimal('average_cost', 10, 2)->nullable()->after('standard_cost');
            $table->decimal('selling_price', 10, 2)->nullable()->after('average_cost');
            $table->foreignId('preferred_supplier_id')->nullable()->after('selling_price')->constrained('suppliers')->nullOnDelete();
            $table->decimal('max_stock', 10, 2)->nullable()->after('reorder_level');
            $table->decimal('min_stock', 10, 2)->nullable()->after('max_stock');
            $table->string('barcode')->nullable()->after('min_stock');
            $table->text('qr_code')->nullable()->after('barcode');
            $table->json('images')->nullable()->after('qr_code');
            $table->text('specification')->nullable()->after('images');
            $table->string('datasheet_path')->nullable()->after('specification');
            $table->string('safety_datasheet_path')->nullable()->after('datasheet_path');
            $table->string('status')->default('Active')->after('safety_datasheet_path'); // Active, Inactive, Discontinued
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('subcategory_id');
            $table->dropConstrainedForeignId('preferred_supplier_id');
            $table->dropColumn([
                'material_code', 'description', 'standard_cost', 'average_cost', 'selling_price',
                'max_stock', 'min_stock', 'barcode', 'qr_code', 'images', 'specification',
                'datasheet_path', 'safety_datasheet_path', 'status',
            ]);
        });
    }
};
