<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_wastages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('waste_type')->default('Damaged'); // Damaged, Lost, Expired, Overuse
            $table->decimal('quantity', 12, 2);
            $table->text('reason')->nullable();
            $table->json('photos')->nullable();
            $table->text('corrective_action')->nullable();
            $table->string('status')->default('Pending'); // Pending, Approved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_wastages');
    }
};
