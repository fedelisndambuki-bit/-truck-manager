<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->foreignId('technician_id')->nullable()->after('equipment_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('maintenance_work_order_id')->nullable()->after('technician_id')->constrained('maintenance_work_orders')->nullOnDelete();
            $table->decimal('downtime_hours', 8, 2)->nullable()->after('cost');
            $table->text('recommendations')->nullable()->after('notes');
            $table->boolean('warranty_covered')->default(false)->after('recommendations');
            $table->json('photos')->nullable()->after('warranty_covered');
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('technician_id');
            $table->dropConstrainedForeignId('maintenance_work_order_id');
            $table->dropColumn(['downtime_hours', 'recommendations', 'warranty_covered', 'photos']);
        });
    }
};
