<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_downtimes', function (Blueprint $table) {
            $table->foreignId('production_line_id')->nullable()->after('crushing_plant_id')->constrained()->nullOnDelete();
            $table->foreignId('crusher_id')->nullable()->after('production_line_id')->constrained()->nullOnDelete();
            $table->foreignId('screen_id')->nullable()->after('crusher_id')->constrained()->nullOnDelete();
            $table->foreignId('conveyor_id')->nullable()->after('screen_id')->constrained()->nullOnDelete();
            $table->foreignId('feeder_id')->nullable()->after('conveyor_id')->constrained()->nullOnDelete();
            $table->string('approval_status')->nullable()->after('corrective_action');
        });
    }

    public function down(): void
    {
        Schema::table('production_downtimes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_line_id');
            $table->dropConstrainedForeignId('crusher_id');
            $table->dropConstrainedForeignId('screen_id');
            $table->dropConstrainedForeignId('conveyor_id');
            $table->dropConstrainedForeignId('feeder_id');
            $table->dropColumn('approval_status');
        });
    }
};
