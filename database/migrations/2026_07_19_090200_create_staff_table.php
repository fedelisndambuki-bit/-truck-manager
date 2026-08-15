<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('staff_number')->nullable()->unique();
            $table->string('photo')->nullable();
            $table->string('full_name');
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('national_id')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('kra_pin')->nullable();
            $table->string('nssf_number')->nullable();
            $table->string('sha_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->text('physical_address')->nullable();
            $table->string('county')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('employment_type')->default('Permanent'); // Permanent, Contract, Casual, Probation
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->decimal('allowances', 12, 2)->nullable()->default(0);
            $table->date('date_hired')->nullable();
            $table->date('contract_expiry')->nullable();
            $table->text('medical_info')->nullable();
            $table->json('ppe_sizes')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('Active'); // Active, On Leave, Suspended, Terminated, Probation
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
