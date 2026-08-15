<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_deliveries', function (Blueprint $table) {
            $table->string('vehicle')->nullable()->after('supplier_name');
            $table->string('driver_name')->nullable()->after('vehicle');
            $table->dateTime('arrival_time')->nullable()->after('driver_name');
            $table->dateTime('offloading_time')->nullable()->after('arrival_time');
            $table->string('inspection_status')->nullable()->after('offloading_time'); // Pending, Passed, Failed
            $table->foreignId('received_by_id')->nullable()->after('inspection_status')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('material_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by_id');
            $table->dropColumn(['vehicle', 'driver_name', 'arrival_time', 'offloading_time', 'inspection_status']);
        });
    }
};
