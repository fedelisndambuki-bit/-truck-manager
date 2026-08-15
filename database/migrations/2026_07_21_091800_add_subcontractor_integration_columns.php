<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('subcontractor_id')->nullable()->after('grn_id')->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->after('subcontractor_id')->constrained()->nullOnDelete();
            $table->foreignId('variation_id')->nullable()->after('contract_id')->constrained()->nullOnDelete();
        });

        Schema::table('quality_inspections', function (Blueprint $table) {
            $table->foreignId('subcontractor_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
        });

        Schema::table('safety_incidents', function (Blueprint $table) {
            $table->foreignId('subcontractor_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
        });

        Schema::table('material_allocations', function (Blueprint $table) {
            $table->foreignId('subcontractor_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
        });

        Schema::table('equipment_allocations', function (Blueprint $table) {
            $table->foreignId('subcontractor_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcontractor_id');
            $table->dropConstrainedForeignId('contract_id');
            $table->dropConstrainedForeignId('variation_id');
        });

        Schema::table('quality_inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcontractor_id');
        });

        Schema::table('safety_incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcontractor_id');
        });

        Schema::table('material_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcontractor_id');
        });

        Schema::table('equipment_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcontractor_id');
        });
    }
};
