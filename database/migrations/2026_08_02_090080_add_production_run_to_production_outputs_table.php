<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->foreignId('production_log_id')->nullable()->change();
            $table->foreignId('production_run_id')->nullable()->after('production_log_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_run_id');
            $table->foreignId('production_log_id')->nullable(false)->change();
        });
    }
};
