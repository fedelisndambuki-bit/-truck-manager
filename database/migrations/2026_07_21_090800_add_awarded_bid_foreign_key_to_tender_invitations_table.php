<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_invitations', function (Blueprint $table) {
            $table->foreign('awarded_bid_id')->references('id')->on('bid_submissions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tender_invitations', function (Blueprint $table) {
            $table->dropForeign(['awarded_bid_id']);
        });
    }
};
