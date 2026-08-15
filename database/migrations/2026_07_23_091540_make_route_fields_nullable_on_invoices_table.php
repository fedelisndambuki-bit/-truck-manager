<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Construction client invoices (Project Finance module) are not tied to a
        // trucking trip and have no route — relax these to nullable so both use
        // cases can share the same `invoices` table.
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('route_from')->nullable()->change();
            $table->string('route_to')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('route_from')->nullable(false)->change();
            $table->string('route_to')->nullable(false)->change();
        });
    }
};
