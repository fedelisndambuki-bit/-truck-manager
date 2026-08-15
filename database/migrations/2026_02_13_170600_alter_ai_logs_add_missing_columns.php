<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_logs')) {
            return;
        }

        Schema::table('ai_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_logs', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('ai_logs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('company_id')->index();
            }
            if (!Schema::hasColumn('ai_logs', 'question')) {
                $table->text('question')->after('user_id');
            }
            if (!Schema::hasColumn('ai_logs', 'response')) {
                $table->longText('response')->nullable()->after('question');
            }
            if (!Schema::hasColumn('ai_logs', 'fleet_data')) {
                $table->json('fleet_data')->nullable()->after('response');
            }
            if (!Schema::hasColumn('ai_logs', 'response_time_ms')) {
                $table->integer('response_time_ms')->nullable()->after('fleet_data');
            }
            if (!Schema::hasColumn('ai_logs', 'status')) {
                $table->string('status')->default('success')->after('response_time_ms');
            }
            if (!Schema::hasColumn('ai_logs', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_logs')) {
            return;
        }

        Schema::table('ai_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_logs', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('ai_logs', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('ai_logs', 'question')) {
                $table->dropColumn('question');
            }
            if (Schema::hasColumn('ai_logs', 'response')) {
                $table->dropColumn('response');
            }
            if (Schema::hasColumn('ai_logs', 'fleet_data')) {
                $table->dropColumn('fleet_data');
            }
            if (Schema::hasColumn('ai_logs', 'response_time_ms')) {
                $table->dropColumn('response_time_ms');
            }
            if (Schema::hasColumn('ai_logs', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('ai_logs', 'error_message')) {
                $table->dropColumn('error_message');
            }
        });
    }
};

