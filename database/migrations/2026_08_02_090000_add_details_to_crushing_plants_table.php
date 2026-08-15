<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crushing_plants', function (Blueprint $table) {
            $table->string('plant_code')->nullable()->unique()->after('id');
            $table->string('location')->nullable()->after('name');
            $table->date('commission_date')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crushing_plants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropColumn(['plant_code', 'location', 'commission_date', 'manufacturer', 'model', 'serial_number']);
        });
    }
};
