<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('variation_number')->unique();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->decimal('cost_impact', 15, 2)->default(0);
            $table->unsignedInteger('time_extension_days')->default(0);
            $table->string('status')->default('Requested');
            $table->foreignId('requested_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variations');
    }
};
