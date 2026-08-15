<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('truck_limit')->default(0);
            $table->unsignedInteger('driver_limit')->default(0);
            $table->unsignedInteger('ai_requests_limit')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'plan_id')) {
                $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'plan_id')) {
                $table->dropForeign(['plan_id']);
            }
        });

        Schema::dropIfExists('plans');
    }
};
