<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proof_of_deliveries', function (Blueprint $table) {
            $table->decimal('rejected_quantity', 12, 2)->nullable()->after('delivered_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('proof_of_deliveries', function (Blueprint $table) {
            $table->dropColumn('rejected_quantity');
        });
    }
};
