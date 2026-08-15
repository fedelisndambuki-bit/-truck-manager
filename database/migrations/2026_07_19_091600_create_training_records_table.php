<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('training_type')->default('Safety Induction'); // Safety Induction, Equipment Training, Technical, First Aid, Fire Safety, Toolbox Talk, Refresher
            $table->string('title')->nullable();
            $table->date('date');
            $table->date('expiry_date')->nullable();
            $table->string('provider')->nullable();
            $table->string('status')->default('Completed'); // Scheduled, Completed, Expired
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_records');
    }
};
