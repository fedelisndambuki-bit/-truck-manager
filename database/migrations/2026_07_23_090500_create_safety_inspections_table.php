<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('inspection_number')->nullable();
            $table->string('inspection_type')->default('Daily Site Inspection');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('date');
            $table->string('result')->default('Pass'); // Pass, Fail, Needs Review, Pass with Observations
            $table->decimal('score', 5, 2)->nullable();
            $table->text('defects')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('photos')->nullable();
            $table->string('status')->default('Draft'); // Draft, Submitted, Approved
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('safety_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safety_inspection_id')->constrained()->cascadeOnDelete();
            $table->string('item_text');
            $table->string('status')->default('Pass'); // Pass, Fail, N/A
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_inspection_items');
        Schema::dropIfExists('safety_inspections');
    }
};
