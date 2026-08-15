<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_orders', function (Blueprint $table) {
            $table->string('vehicle_registration')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_orders', function (Blueprint $table) {
            $table->string('vehicle_registration')->nullable(false)->change();
        });
    }
};
