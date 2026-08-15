<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('brand_settings', 'company_name')) {
                $table->string('company_name')->nullable()->after('id');
            }
            if (! Schema::hasColumn('brand_settings', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('company_name')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Intentionally no destructive rollback for compatibility migration.
    }
};
