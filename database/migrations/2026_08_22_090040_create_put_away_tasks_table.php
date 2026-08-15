<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('put_away_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('task_number')->unique();
            $table->foreignId('goods_received_note_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->foreignId('recommended_bin_id')->nullable()->constrained('bin_locations')->nullOnDelete();
            $table->foreignId('actual_bin_id')->nullable()->constrained('bin_locations')->nullOnDelete();
            $table->enum('status', ['Pending', 'Completed', 'Cancelled'])->default('Pending');
            $table->foreignId('completed_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('put_away_tasks');
    }
};
