<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diary_delays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('daily_site_diary_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('Other'); // Weather, Equipment, Material, Labour, Other
            $table->text('reason');
            $table->decimal('duration_hours', 5, 2)->default(0);
            $table->string('impact')->default('Low'); // Low, Medium, High, Critical
            $table->string('responsible_party')->nullable();
            $table->text('recovery_plan')->nullable();
            $table->string('status')->default('Open'); // Open, Resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diary_delays');
    }
};
