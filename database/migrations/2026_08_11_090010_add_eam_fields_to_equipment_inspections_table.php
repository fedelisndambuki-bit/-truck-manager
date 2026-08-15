<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->json('checklist')->nullable()->after('type');
            $table->json('videos')->nullable()->after('photos');
            $table->string('gps_coordinates')->nullable()->after('videos');
            $table->foreignId('operator_id')->nullable()->after('inspector_id')->constrained('staff')->nullOnDelete();
            $table->boolean('pass_fail')->nullable()->after('supervisor_approved');
            $table->text('corrective_actions')->nullable()->after('pass_fail');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_id');
            $table->dropColumn(['checklist', 'videos', 'gps_coordinates', 'pass_fail', 'corrective_actions']);
        });
    }
};
