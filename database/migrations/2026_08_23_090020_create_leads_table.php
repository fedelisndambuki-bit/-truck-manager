<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('lead_number')->unique();
            $table->string('source')->nullable();
            $table->string('company_name');
            $table->string('contact_person')->nullable();
            $table->string('industry')->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('interest')->nullable();
            $table->decimal('expected_volume', 14, 2)->nullable();
            $table->decimal('estimated_value', 14, 2)->nullable();
            $table->foreignId('assigned_to_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->enum('priority', ['Low', 'Normal', 'High', 'Urgent'])->default('Normal');
            $table->enum('status', ['New', 'Qualified', 'Contacted', 'Proposal Sent', 'Negotiation', 'Converted', 'Lost'])->default('New');
            $table->foreignId('converted_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
