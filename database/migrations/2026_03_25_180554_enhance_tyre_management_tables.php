<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tyres', function (Blueprint $table) {
            $table->date('purchase_date')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->text('warranty_details')->nullable();
            $table->string('tyre_category')->nullable(); // steer, drive, trailer
            $table->decimal('pressure', 8, 2)->nullable();
            $table->integer('retread_count')->default(0);
            $table->string('warehouse_location')->nullable();
            $table->string('removal_reason')->nullable();
        });

        Schema::create('tyre_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tyre_id')->constrained()->onDelete('cascade');
            $table->date('inspection_date');
            $table->decimal('tread_depth', 8, 2)->nullable();
            $table->decimal('pressure', 8, 2)->nullable();
            $table->decimal('temperature', 8, 2)->nullable();
            $table->json('checklist')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tyre_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tyre_id')->constrained()->onDelete('cascade');
            $table->date('repair_date');
            $table->string('repair_type')->nullable(); // puncture, vulcanization, retread
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('vendor')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyre_repairs');
        Schema::dropIfExists('tyre_inspections');
        Schema::table('tyres', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_date',
                'supplier',
                'purchase_cost',
                'warranty_details',
                'tyre_category',
                'pressure',
                'retread_count',
                'warehouse_location',
                'removal_reason',
            ]);
        });
    }
};
