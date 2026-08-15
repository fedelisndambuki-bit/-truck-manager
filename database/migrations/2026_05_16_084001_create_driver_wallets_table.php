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
        Schema::create('driver_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('driver_id')->unique();
            $table->unsignedBigInteger('company_id');
            $table->bigInteger('balance')->default(0);
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->index(['company_id', 'driver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_wallets');
    }
};
