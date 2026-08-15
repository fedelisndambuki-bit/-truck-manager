<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('description')->nullable()->after('material_id');
            $table->unsignedBigInteger('material_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->unsignedBigInteger('material_id')->nullable(false)->change();
        });
    }
};
