<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();

            // Requester
            $table->string('requester_name');
            $table->string('requester_email')->index();
            $table->string('requester_phone')->nullable();

            // Organization
            $table->string('organization_name')->index();
            $table->string('organization_country', 2)->nullable();
            $table->string('organization_type', 60)->nullable();
            $table->string('organization_size', 30)->nullable();
            $table->json('operations_of_interest')->nullable();
            $table->text('operation_description')->nullable();

            // Status / lifecycle
            $table->string('status', 40)->default('pending_email_verification')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Consent
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_policy_version', 20)->nullable();

            // Risk signals
            $table->boolean('domain_is_risky')->default(false);
            $table->string('domain_risk_reason')->nullable();
            $table->boolean('captcha_verified')->default(false);

            // Email verification (bespoke — no User exists yet at this point)
            $table->string('email_verification_token', 64)->nullable()->unique();
            $table->timestamp('email_verification_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            // Set only once approved
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['requester_email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
