<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('source_type'); // progress_claim, payment_certificate
            $table->unsignedBigInteger('source_id');
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('held_amount', 15, 2)->default(0);
            $table->decimal('released_amount', 15, 2)->default(0);
            $table->date('dlp_end_date')->nullable();
            $table->string('release_status')->default('Held'); // Held, Partially Released, Released
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_entries');
    }
};
