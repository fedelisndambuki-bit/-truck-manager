<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_allocations', function (Blueprint $table) {
            $table->foreignId('extraction_site_id')->nullable()->after('site_id')->constrained('extraction_sites')->nullOnDelete();
            $table->foreignId('crusher_id')->nullable()->after('extraction_site_id')->constrained('crushers')->nullOnDelete();
            $table->foreignId('stockpile_id')->nullable()->after('crusher_id')->constrained('stockpiles')->nullOnDelete();
            $table->string('loading_bay')->nullable()->after('stockpile_id');
            $table->string('production_area')->nullable()->after('loading_bay');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('extraction_site_id');
            $table->dropConstrainedForeignId('crusher_id');
            $table->dropConstrainedForeignId('stockpile_id');
            $table->dropColumn(['loading_bay', 'production_area']);
        });
    }
};
