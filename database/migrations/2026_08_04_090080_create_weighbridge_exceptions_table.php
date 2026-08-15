<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weighbridge_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('exception_number')->nullable()->unique();
            $table->foreignId('dispatch_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exception_type');
            $table->text('reason');
            $table->text('comments')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            $table->foreignId('reported_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('Draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weighbridge_exceptions');
    }
};
