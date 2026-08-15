<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('date');
            $table->string('severity')->default('Low'); // Low, Medium, High, Critical
            $table->text('description')->nullable();
            $table->string('status')->default('Open'); // Open, Investigating, Closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_incidents');
    }
};
