<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'tax_number')) {
                $table->string('tax_number')->nullable();
            }
            if (!Schema::hasColumn('clients', 'emergency_contact')) {
                $table->string('emergency_contact')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['tax_number', 'emergency_contact']);
        });
    }
};
