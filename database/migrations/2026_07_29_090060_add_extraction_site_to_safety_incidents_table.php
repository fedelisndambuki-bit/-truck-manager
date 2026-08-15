<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safety_incidents', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->change();
            $table->foreignId('extraction_site_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('safety_incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('extraction_site_id');
            $table->foreignId('site_id')->nullable(false)->change();
        });
    }
};
