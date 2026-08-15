<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Previously the only link between the mobile-app-facing Driver
        // model and the HR Employee model was a fragile runtime match on
        // Employee.email == User.email (see the old
        // TripObserver::creditDriverWallet(), which silently no-opped on
        // any mismatch). This makes the link a real, queryable foreign key.
        Schema::table('drivers', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('user_id')->constrained('employees')->nullOnDelete();
        });

        // Best-effort one-time backfill for existing rows, using the exact
        // same email-match logic being retired — anything it can't resolve
        // stays null and needs manual linking going forward via a real FK.
        $drivers = DB::table('drivers')->whereNull('employee_id')->get();

        foreach ($drivers as $driver) {
            $user = DB::table('users')->where('id', $driver->user_id)->first();
            if (! $user) {
                continue;
            }

            $employee = DB::table('employees')
                ->where('company_id', $driver->company_id)
                ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
                ->first();

            if ($employee) {
                DB::table('drivers')->where('id', $driver->id)->update(['employee_id' => $employee->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
