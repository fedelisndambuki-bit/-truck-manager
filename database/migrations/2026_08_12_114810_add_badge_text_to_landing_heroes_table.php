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
        Schema::table('landing_heroes', function (Blueprint $table) {
            $table->string('badge_text')->nullable()->after('subheadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_heroes', function (Blueprint $table) {
            $table->dropColumn('badge_text');
        });
    }
};
