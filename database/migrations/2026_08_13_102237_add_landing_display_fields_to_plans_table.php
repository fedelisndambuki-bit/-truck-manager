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
        // Prior to this, the landing page's tagline, "access level" phrase,
        // and CTA label for each plan were hardcoded in the blade template
        // (matched by plan name) — editing a Plan in the Control Room had no
        // effect on them. This makes them real, editable fields.
        Schema::table('plans', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('name');
            $table->string('access_level_label')->nullable()->after('tagline');
            $table->string('cta_label')->nullable()->after('access_level_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'access_level_label', 'cta_label']);
        });
    }
};
