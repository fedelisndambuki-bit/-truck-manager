<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('daily_site_diary_id')->nullable()->after('grn_id')->constrained()->nullOnDelete();
            $table->foreignId('diary_delay_id')->nullable()->after('daily_site_diary_id')->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('diary_delay_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->timestamp('taken_at')->nullable()->after('longitude');
            $table->string('photo_category')->nullable()->after('taken_at'); // Progress, Equipment, Material, Drone, Incident, Inspection, Blueprint Markup
            $table->json('annotations')->nullable()->after('photo_category');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daily_site_diary_id');
            $table->dropConstrainedForeignId('diary_delay_id');
            $table->dropColumn(['latitude', 'longitude', 'taken_at', 'photo_category', 'annotations']);
        });
    }
};
