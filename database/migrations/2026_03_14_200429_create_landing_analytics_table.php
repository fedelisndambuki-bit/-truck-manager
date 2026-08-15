<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('landing_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('visitors')->default(0);
            $table->integer('trial_signups')->default(0);
            $table->integer('paid_conversions')->default(0);
            $table->string('traffic_source')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_analytics');
    }
};
