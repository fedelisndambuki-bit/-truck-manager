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
        Schema::create('landing_ctas', function (Blueprint $table) {
            $table->id();
            $table->string('headline');
            $table->string('subtext')->nullable();
            $table->string('button_text');
            $table->string('button_link');
            $table->string('background_style')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_ctas');
    }
};
