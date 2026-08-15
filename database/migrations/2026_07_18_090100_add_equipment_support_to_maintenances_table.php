<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->foreignId('truck_id')->nullable()->change();
            $table->foreignId('equipment_id')->nullable()->after('truck_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipment_id');
            $table->foreignId('truck_id')->nullable(false)->change();
        });
    }
};
