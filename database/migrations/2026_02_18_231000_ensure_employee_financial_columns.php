<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'base_salary')) {
                $table->decimal('base_salary', 10, 2)->default(0.00)->after('email');
            }
            if (! Schema::hasColumn('employees', 'allowances')) {
                $table->decimal('allowances', 10, 2)->default(0.00)->after('base_salary');
            }
            if (! Schema::hasColumn('employees', 'tax_deduction')) {
                $table->decimal('tax_deduction', 10, 2)->default(0.00)->after('allowances');
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty to avoid dropping existing production columns.
    }
};
