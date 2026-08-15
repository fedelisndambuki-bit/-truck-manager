<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Trip already supports client-less operation (fallback client_name/
        // client_contact stored directly on the trip), but the auto-generated
        // invoice crashed on save whenever trip.client_id was null.
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable(false)->change();
        });
    }
};
