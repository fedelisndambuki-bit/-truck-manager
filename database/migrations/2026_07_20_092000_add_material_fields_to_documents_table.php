<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('material_id')->nullable()->after('staff_id')->constrained()->nullOnDelete();
            $table->foreignId('grn_id')->nullable()->after('material_id')->constrained('goods_received_notes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_id');
            $table->dropConstrainedForeignId('grn_id');
        });
    }
};
