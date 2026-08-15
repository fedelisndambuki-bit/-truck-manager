<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_stock_transfers', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('vehicle_registration');
            $table->string('received_status')->default('Pending')->after('status');
            $table->foreignId('received_by_id')->nullable()->after('received_status')->constrained('staff')->nullOnDelete();
            $table->timestamp('received_at')->nullable()->after('received_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('production_stock_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by_id');
            $table->dropColumn(['driver_name', 'received_status', 'received_at']);
        });
    }
};
