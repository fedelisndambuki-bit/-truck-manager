<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->string('service_provider')->nullable()->change();
            $table->decimal('cost', 15, 2)->nullable()->change();
            $table->integer('mileage_at_service')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
            $table->string('service_provider')->nullable(false)->change();
            $table->decimal('cost', 15, 2)->nullable(false)->change();
            $table->integer('mileage_at_service')->nullable(false)->change();
        });
    }
};
