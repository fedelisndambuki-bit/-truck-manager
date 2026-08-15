<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_incident_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safety_incident_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('Photo'); // Photo, Video, Document
            $table->string('caption')->nullable();
            $table->string('file_path');
            $table->string('disk')->default('public');
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_incident_attachments');
    }
};
