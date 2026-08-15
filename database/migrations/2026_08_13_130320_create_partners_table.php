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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo');
            $table->string('website_url')->nullable();
            $table->string('category')->default('technology');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('status')->default(true);
            $table->boolean('grayscale')->default(true);
            $table->timestamps();
        });

        // Seed the landing page copy for this section into the existing
        // generic landing_sections mechanism (already editable via the
        // Control Room's "Section Headings" resource) — no separate
        // heading/subtext fields needed on the partners table itself.
        \Illuminate\Support\Facades\DB::table('landing_sections')->insert([
            'key' => 'partners',
            'badge_text' => 'Technology & Integrations',
            'heading' => 'Built with trusted technology',
            'subtext' => 'Tanaki Hub connects the systems, devices and services modern industrial operations rely on.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');

        \Illuminate\Support\Facades\DB::table('landing_sections')->where('key', 'partners')->delete();
    }
};
