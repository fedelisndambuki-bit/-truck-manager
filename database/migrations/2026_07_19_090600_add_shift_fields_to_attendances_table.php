<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
            $table->string('check_in_method')->nullable()->after('check_out_at'); // Manual, GPS, QR, Biometric
            $table->boolean('late')->default(false)->after('check_in_method');
            $table->boolean('early_departure')->default(false)->after('late');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
            $table->dropColumn(['check_in_method', 'late', 'early_departure']);
        });
    }
};
