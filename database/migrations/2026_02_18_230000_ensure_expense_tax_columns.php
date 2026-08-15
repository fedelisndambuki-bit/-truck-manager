<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'tax_type')) {
                $table->text('tax_type')->nullable()->after('category');
            }
            if (! Schema::hasColumn('expenses', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->nullable()->after('tax_type');
            }
            if (! Schema::hasColumn('expenses', 'tax_breakdown')) {
                $table->json('tax_breakdown')->nullable()->after('tax_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'tax_breakdown')) {
                $table->dropColumn('tax_breakdown');
            }
        });
    }
};
