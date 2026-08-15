<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The pivot table doesn't need its own company_id: no Eloquent model/global
     * scope reads it (the relation is a plain string-table belongsToMany), and
     * its presence causes an ambiguous-column SQL error when joined against
     * `subcontractors` (which IS company-scoped) — both tables end up with an
     * unqualified `company_id` in the same query.
     */
    public function up(): void
    {
        Schema::table('tender_invitation_subcontractor', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('tender_invitation_subcontractor', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }
};
