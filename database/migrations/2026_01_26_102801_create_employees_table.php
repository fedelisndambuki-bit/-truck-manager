<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('full_name');
            $table->string('phone_number')->unique();
            $table->string('email')->unique()->nullable();

            // Identity & License
            $table->string('national_id')->unique();
            $table->string('license_number')->unique();
            $table->date('license_expiry');

            // Job Details
            $table->enum('category', ['Local', 'Long Distance'])->default('Local');
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            $table->enum('status', ['Active', 'On Leave', 'Suspended'])->default('Active');
            $table->date('date_hired');

            // Emergency & Extra
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('profile_photo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
