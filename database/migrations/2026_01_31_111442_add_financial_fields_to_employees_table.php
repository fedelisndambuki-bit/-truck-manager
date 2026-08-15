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
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('base_salary', 10, 2)->default(0.00)->after('email');
            $table->decimal('allowances', 10, 2)->default(0.00)->after('base_salary');
            $table->decimal('tax_deduction', 10, 2)->default(0.00)->after('allowances');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('base_salary');
            $table->dropColumn('allowances');
            $table->dropColumn('tax_deduction');
        });
    }
};