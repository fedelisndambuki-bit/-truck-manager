<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('payslip_earning_type')->nullable();
            $table->string('payslip_deduction_type')->nullable();
            $table->decimal('hours', 10, 2)->nullable();
            $table->decimal('rate', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn([
                'payslip_earning_type',
                'payslip_deduction_type',
                'hours',
                'rate',
            ]);
        });
    }
};
