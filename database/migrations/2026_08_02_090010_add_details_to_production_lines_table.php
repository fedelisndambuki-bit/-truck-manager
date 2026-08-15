<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->string('line_code')->nullable()->unique()->after('id');
            $table->decimal('current_capacity_tph', 10, 2)->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropColumn(['line_code', 'current_capacity_tph']);
        });
    }
};
