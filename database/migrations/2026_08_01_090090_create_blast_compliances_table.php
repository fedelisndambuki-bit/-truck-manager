<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blast_compliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('blast_plan_id')->constrained()->cascadeOnDelete();
            $table->string('permit_number')->nullable();
            $table->date('permit_expiry')->nullable();
            $table->boolean('regulatory_approval')->default(false);
            $table->boolean('blast_notifications_sent')->default(false);
            $table->boolean('inspector_approval')->default(false);
            $table->boolean('safety_checklist_complete')->default(false);
            $table->boolean('environmental_checklist_complete')->default(false);
            $table->boolean('legal_compliance')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_compliances');
    }
};
