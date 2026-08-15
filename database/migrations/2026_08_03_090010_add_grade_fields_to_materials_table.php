<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('name');
            $table->string('selling_unit')->nullable()->after('unit');
            $table->string('quality_standard')->nullable()->after('selling_unit');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['grade', 'selling_unit', 'quality_standard']);
        });
    }
};
