<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link payments to invoices
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            }
        });

        // Link expenses to trips, track approver
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'approver_id')) {
                $table->foreignId('approver_id')->nullable()->after('trip_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approver_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('invoice_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['trip_id', 'approver_id', 'approved_at']);
        });
    }
};
