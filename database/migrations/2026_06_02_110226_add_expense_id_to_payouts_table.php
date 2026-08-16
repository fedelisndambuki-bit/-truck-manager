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
        Schema::table('fin_payouts', function (Blueprint $column) {
            $column->uuid('expense_id')->nullable()->after('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fin_payouts', function (Blueprint $column) {
            $column->dropColumn('expense_id');
        });
    }
};
