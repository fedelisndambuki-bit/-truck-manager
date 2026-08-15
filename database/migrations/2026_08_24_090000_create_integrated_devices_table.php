<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrated_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('vendor_key');
            $table->string('category');
            $table->string('connection_medium')->nullable();
            $table->string('protocol');
            $table->json('connection_config')->nullable();
            $table->enum('status', ['active', 'inactive', 'offline', 'faulted'])->default('inactive');
            $table->string('health_status')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrated_devices');
    }
};
