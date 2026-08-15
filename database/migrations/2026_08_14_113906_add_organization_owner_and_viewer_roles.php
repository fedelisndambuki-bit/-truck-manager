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
        // 'Organization Owner' — the founding user of a newly-approved
        // access request. 'Viewer' — a genuinely missing read-only
        // baseline role. Both use firstOrCreate so this migration is safe
        // to run against an existing production database without
        // duplicating roles or requiring the static RoleSeeder to be
        // re-run.
        foreach (['Organization Owner', 'Viewer'] as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Spatie\Permission\Models\Role::whereIn('name', ['Organization Owner', 'Viewer'])
            ->where('guard_name', 'web')
            ->delete();
    }
};
