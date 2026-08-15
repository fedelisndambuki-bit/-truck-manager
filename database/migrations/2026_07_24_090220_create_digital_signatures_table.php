<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('signable_type');
            $table->unsignedBigInteger('signable_id');
            $table->foreignId('signer_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('signer_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_hash')->nullable();
            $table->text('signature_data')->nullable(); // typed name or base64 drawn signature
            $table->string('ip_address')->nullable();
            $table->boolean('verified')->default(true);
            $table->timestamps();

            $table->index(['signable_type', 'signable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_signatures');
    }
};
