<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable()->after('name');
            }

            if (! Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (! Schema::hasColumn('companies', 'status')) {
                $table->string('status')->default('active')->after('phone');
            }

            if (! Schema::hasColumn('companies', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('status');
            }

            if (! Schema::hasColumn('companies', 'subscription_id')) {
                $table->unsignedBigInteger('subscription_id')->nullable()->after('plan_id');
            }

            if (! Schema::hasColumn('companies', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('subscription_id');
            }

            if (! Schema::hasColumn('companies', 'address')) {
                $table->string('address')->nullable()->after('trial_ends_at');
            }

            if (! Schema::hasColumn('companies', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('address');
            }

            if (! Schema::hasColumn('companies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('tax_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'plan_id')) {
                $table->dropColumn('plan_id');
            }

            foreach (['subscription_id', 'trial_ends_at', 'status'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
