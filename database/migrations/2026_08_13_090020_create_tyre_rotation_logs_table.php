<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tyre_rotation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('tyre_id')->constrained()->cascadeOnDelete();
            $table->string('from_position')->nullable();
            $table->string('to_position');
            $table->dateTime('rotated_at');
            $table->unsignedInteger('mileage_at_rotation')->nullable();
            $table->foreignId('performed_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyre_rotation_logs');
    }
};
