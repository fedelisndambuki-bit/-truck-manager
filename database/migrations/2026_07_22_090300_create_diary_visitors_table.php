<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diary_visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('daily_site_diary_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_name');
            $table->string('company')->nullable();
            $table->string('purpose')->nullable();
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->foreignId('host_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diary_visitors');
    }
};
