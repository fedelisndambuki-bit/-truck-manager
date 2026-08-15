<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->string('production_number')->nullable()->unique()->after('id');
            $table->foreignId('quarry_face_id')->nullable()->after('extraction_site_id')->constrained()->nullOnDelete();
            $table->foreignId('pit_id')->nullable()->after('quarry_face_id')->constrained()->nullOnDelete();
            $table->foreignId('production_line_id')->nullable()->after('pit_id')->constrained()->nullOnDelete();
            $table->foreignId('crushing_plant_id')->nullable()->after('production_line_id')->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->after('shift')->constrained()->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->after('logged_by_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->after('supervisor_id')->constrained('staff')->nullOnDelete();
            $table->string('status')->default('Draft')->after('operator_id'); // Draft, Submitted, Approved, Closed
            $table->decimal('feed_quantity', 12, 2)->nullable();
            $table->decimal('crusher_throughput', 12, 2)->nullable();
            $table->decimal('finished_product_quantity', 12, 2)->nullable();
            $table->decimal('rejected_material', 12, 2)->nullable();
            $table->decimal('waste_material', 12, 2)->nullable();
            $table->decimal('by_products', 12, 2)->nullable();
            $table->decimal('recovery_percent', 5, 2)->nullable();
            $table->decimal('yield_percent', 5, 2)->nullable();
            $table->decimal('operating_hours', 6, 2)->nullable();
            $table->decimal('idle_hours', 6, 2)->nullable();
            $table->unsignedInteger('downtime_minutes')->default(0);
            $table->text('remarks')->nullable();
            $table->string('attachment_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('production_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quarry_face_id');
            $table->dropConstrainedForeignId('pit_id');
            $table->dropConstrainedForeignId('production_line_id');
            $table->dropConstrainedForeignId('crushing_plant_id');
            $table->dropConstrainedForeignId('shift_id');
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropConstrainedForeignId('operator_id');
            $table->dropColumn([
                'production_number', 'status', 'feed_quantity', 'crusher_throughput',
                'finished_product_quantity', 'rejected_material', 'waste_material', 'by_products',
                'recovery_percent', 'yield_percent', 'operating_hours', 'idle_hours',
                'downtime_minutes', 'remarks', 'attachment_path',
            ]);
        });
    }
};
