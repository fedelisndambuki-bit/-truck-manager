<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('plan_name');
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('truck_limit')->default(0);
            $table->unsignedInteger('driver_limit')->default(0);
            $table->unsignedInteger('ai_limit')->default(0);
            $table->string('billing_cycle')->default('monthly');
            $table->string('status')->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'subscription_id')) {
                $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'subscription_id')) {
                $table->dropForeign(['subscription_id']);
            }
        });

        Schema::dropIfExists('subscriptions');
    }
};
