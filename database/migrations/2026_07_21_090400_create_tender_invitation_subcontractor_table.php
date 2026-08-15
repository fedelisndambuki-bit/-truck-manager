<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_invitation_subcontractor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('tender_invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcontractor_id')->constrained()->cascadeOnDelete();
            $table->dateTime('invited_at')->nullable();
            $table->boolean('responded')->default(false);
            $table->timestamps();

            $table->unique(['tender_invitation_id', 'subcontractor_id'], 'tender_sub_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_invitation_subcontractor');
    }
};
