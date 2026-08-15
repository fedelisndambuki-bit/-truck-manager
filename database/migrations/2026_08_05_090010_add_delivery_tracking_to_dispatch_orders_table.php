<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_orders', function (Blueprint $table) {
            $table->foreignId('customer_order_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('delivery_address')->nullable();
            $table->decimal('delivery_gps_lat', 10, 6)->nullable();
            $table->decimal('delivery_gps_lng', 10, 6)->nullable();
            $table->decimal('current_gps_lat', 10, 6)->nullable();
            $table->decimal('current_gps_lng', 10, 6)->nullable();
            $table->dateTime('eta')->nullable();
            $table->decimal('distance_remaining_km', 8, 2)->nullable();
            $table->timestamp('geofence_entered_at')->nullable();
            $table->timestamp('geofence_exited_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->text('delivery_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_order_id');
            $table->dropColumn([
                'delivery_address', 'delivery_gps_lat', 'delivery_gps_lng', 'current_gps_lat', 'current_gps_lng',
                'eta', 'distance_remaining_km', 'geofence_entered_at', 'geofence_exited_at', 'arrived_at', 'delivery_notes',
            ]);
        });
    }
};
