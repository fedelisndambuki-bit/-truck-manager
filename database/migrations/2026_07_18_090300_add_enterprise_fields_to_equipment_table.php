<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('equipment_code')->nullable()->unique()->after('id');
            $table->string('registration_number')->nullable()->after('serial_number');
            $table->decimal('fuel_level', 5, 2)->nullable()->after('registration_number'); // %
            $table->decimal('engine_hours', 10, 2)->nullable()->after('fuel_level');
            $table->decimal('idle_hours', 10, 2)->nullable()->after('engine_hours');
            $table->string('insurance_provider')->nullable()->after('idle_hours');
            $table->date('insurance_expiry')->nullable()->after('insurance_provider');
            $table->date('warranty_expiry')->nullable()->after('insurance_expiry');
            $table->decimal('current_value', 14, 2)->nullable()->after('warranty_expiry');
            $table->decimal('purchase_cost', 14, 2)->nullable()->after('current_value');
            $table->string('photo')->nullable()->after('purchase_cost');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn([
                'equipment_code', 'registration_number', 'fuel_level', 'engine_hours', 'idle_hours',
                'insurance_provider', 'insurance_expiry', 'warranty_expiry', 'current_value',
                'purchase_cost', 'photo',
            ]);
        });
    }
};
