<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_bays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->string('bay_number');
            $table->string('status')->default('Available'); // Available, Occupied, Out of Service
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_bays');
    }
};
