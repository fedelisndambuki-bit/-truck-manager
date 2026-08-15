<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('approval_action_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_hash')->nullable();
            $table->string('signature_type')->default('typed'); // typed, drawn
            $table->longText('signature_data')->nullable(); // typed name, or base64 drawn signature
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('verified')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_signatures');
    }
};
